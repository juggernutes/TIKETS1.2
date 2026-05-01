<?php

namespace App\Support;

use App\Models\Com\AjusteComision;
use App\Models\Com\BaseComisionSemanal;
use App\Models\Com\CalculoComision;
use App\Models\Com\CorridaComision;
use App\Models\Com\ResultadoDoc;
use App\Models\Com\ResultadoIndicador;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Motor de cálculo de comisiones.
 *
 * Para cada base (empleado) de la corrida:
 *   1. VOL  — Volumen: % cumplimiento → busca regla → Monto
 *   2. EFE  — Efectividad: ClientesConCompra / ClientesVisitados → Monto
 *   3. EFI  — Eficiencia: ClientesVisitados / ClientesActivos → Monto
 *   4. COB  — Cobertura: por sub-indicador (línea), % → Monto acumulado
 *   5. NSE  — Nivel de servicio: % → Monto
 *   6. DF1  — Dev. F1: ValorReal * Factor (descuento)
 *   7. DAU  — Dev. Autoservicio: ValorReal * Factor (descuento)
 *   8. DOC  — Documentos: suma de AlcancePesos desde resultado_doc
 *   9. Ajustes manuales: DESCUENTO / AGREGADO / SR1 / SR2
 *  10. Guarda calculo_comision con MontoBruto, TotalDescuentos, TotalAgregados, MontoFinal
 */
class ComisionCalculoService
{
    /** @var Collection<int, object> reglas indexadas por clave */
    private Collection $reglas;

    public function calcularCorrida(int $idCorrida, int $idUsuario): array
    {
        $corrida = CorridaComision::with('semana')->findOrFail($idCorrida);

        // Pre-cargar todas las reglas activas de la corrida
        $this->reglas = DB::table('com.regla_comision as rc')
            ->join('com.indicador as i', 'i.ID_Indicador', '=', 'rc.ID_Indicador')
            ->leftJoin('com.sub_indicador as si', 'si.ID_SubIndicador', '=', 'rc.ID_SubIndicador')
            ->where('rc.Activo', true)
            ->select('rc.*', 'i.Clave as ClaveIndicador', 'si.Clave as ClaveSub')
            ->get();

        $bases = BaseComisionSemanal::where('ID_Corrida', $idCorrida)
            ->where('Activo', true)
            ->get();

        $procesados = 0;
        $errores    = [];

        foreach ($bases as $base) {
            try {
                $this->calcularBase($base, $idUsuario);
                $procesados++;
            } catch (\Throwable $e) {
                $errores[] = "Base #{$base->ID_Base} (Emp {$base->Numero_Empleado}): {$e->getMessage()}";
            }
        }

        return [
            'corrida'    => $idCorrida,
            'procesados' => $procesados,
            'errores'    => $errores,
        ];
    }

    private function calcularBase(BaseComisionSemanal $base, int $idUsuario): void
    {
        $resultados = ResultadoIndicador::where('ID_Base', $base->ID_Base)->get()
            ->keyBy(fn ($r) => $r->ID_Indicador . '_' . ($r->ID_SubIndicador ?? '0'));

        $ajustes = AjusteComision::where('ID_Base', $base->ID_Base)->get();

        $montoBruto     = 0.0;
        $totalDescuentos = 0.0;
        $totalAgregados  = 0.0;

        // 1–5. Indicadores de VENTAS y CALIDAD (excepto DF1/DAU/DOC)
        foreach (['VOL', 'EFE', 'EFI', 'COB', 'NSE'] as $clave) {
            $resultadosClave = $this->getResultadosPorClave($resultados, $clave);

            foreach ($resultadosClave as $resultado) {
                $pct = $this->calcularPorcentaje($base, $resultado, $clave);

                $regla = $this->buscarRegla($clave, $base->TCE, $base->Puesto, $base->Canal, $pct, $resultado->ID_SubIndicador);

                $monto = $regla?->Monto ?? 0.0;

                // Guardar PorcentajeCumplimiento y MontoCalculado
                $resultado->PorcentajeCumplimiento = $pct;
                $resultado->MontoCalculado         = $monto;
                $resultado->save();

                $montoBruto += $monto;
            }
        }

        // 6. DF1 — Devolución F1 (descuento)
        $descDF1 = $this->calcularDevolucion($resultados, $base, 'DF1');
        $totalDescuentos += $descDF1;

        // 7. DAU — Devolución autoservicio (descuento)
        $descDAU = $this->calcularDevolucion($resultados, $base, 'DAU');
        $totalDescuentos += $descDAU;

        // 8. DOC — suma de AlcancePesos capturados por el gerente
        $montoDoc = ResultadoDoc::where('ID_Base', $base->ID_Base)->sum('AlcancePesos');
        $montoBruto += (float) $montoDoc;

        // 9. Ajustes manuales
        foreach ($ajustes as $ajuste) {
            match ($ajuste->TipoAjuste) {
                'DESCUENTO' => $totalDescuentos += $ajuste->Monto,
                'AGREGADO'  => $totalAgregados  += $ajuste->Monto,
                'SR1'       => $totalDescuentos += $ajuste->Monto,
                'SR2'       => $totalDescuentos += $ajuste->Monto,
                default     => null,
            };
        }

        $montoFinal = max(0.0, $montoBruto - $totalDescuentos + $totalAgregados);

        // 10. Guardar / actualizar calculo_comision
        CalculoComision::updateOrCreate(
            ['ID_Base' => $base->ID_Base],
            [
                'MontoBruto'      => round($montoBruto, 2),
                'TotalDescuentos' => round($totalDescuentos, 2),
                'TotalAgregados'  => round($totalAgregados, 2),
                'MontoFinal'      => round($montoFinal, 2),
                'Estatus'         => 'CALCULADO',
                'CalculadoPor'    => $idUsuario,
            ]
        );
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Obtiene los resultados de un indicador dado su Clave.
     */
    private function getResultadosPorClave(Collection $resultados, string $clave): Collection
    {
        $idIndicador = $this->reglas
            ->where('ClaveIndicador', $clave)
            ->first()?->ID_Indicador;

        if (!$idIndicador) return collect();

        return $resultados->filter(fn ($r) => $r->ID_Indicador === $idIndicador);
    }

    /**
     * Calcula el porcentaje de cumplimiento según el tipo de indicador.
     */
    private function calcularPorcentaje(BaseComisionSemanal $base, ResultadoIndicador $resultado, string $clave): float
    {
        return match ($clave) {
            'VOL', 'COB' => $resultado->Meta > 0
                ? round((float) $resultado->ValorReal / (float) $resultado->Meta, 6)
                : 0.0,

            'EFE' => $resultado->ClientesVisitados > 0
                ? round($resultado->ClientesConCompra / $resultado->ClientesVisitados, 6)
                : 0.0,

            'EFI' => $resultado->ClientesActivos > 0
                ? round($resultado->ClientesVisitados / $resultado->ClientesActivos, 6)
                : 0.0,

            'NSE' => $resultado->Meta > 0
                ? round((float) $resultado->ValorReal / (float) $resultado->Meta, 6)
                : 0.0,

            default => 0.0,
        };
    }

    /**
     * Busca la regla de comisión más específica que aplique.
     * Prioridad: TCE+Puesto+Canal > solo TCE > solo Puesto > sin filtro.
     */
    private function buscarRegla(
        string  $claveIndicador,
        ?string $tce,
        ?string $puesto,
        ?string $canal,
        float   $pct,
        ?int    $idSubIndicador
    ): ?object {
        return $this->reglas
            ->where('ClaveIndicador', $claveIndicador)
            ->filter(function ($r) use ($tce, $puesto, $canal, $pct, $idSubIndicador) {
                $matchTCE    = $r->TCE   === null || $r->TCE   === $tce;
                $matchPuesto = $r->Puesto === null || $r->Puesto === $puesto;
                $matchCanal  = $r->Canal  === null || $r->Canal  === $canal;
                $matchSub    = $idSubIndicador === null
                    ? $r->ID_SubIndicador === null
                    : ($r->ID_SubIndicador === null || $r->ID_SubIndicador === $idSubIndicador);
                $matchPct    = $pct >= (float) $r->PorcentajeMinimo && $pct <= (float) $r->PorcentajeMaximo;

                return $matchTCE && $matchPuesto && $matchCanal && $matchSub && $matchPct;
            })
            ->sortByDesc(fn ($r) => (
                ($r->TCE    !== null ? 4 : 0) +
                ($r->Puesto !== null ? 2 : 0) +
                ($r->Canal  !== null ? 1 : 0)
            ))
            ->first();
    }

    /**
     * Calcula el descuento por devolución (DF1 o DAU).
     * Descuento = ValorReal * Factor (de la regla).
     */
    private function calcularDevolucion(Collection $resultados, BaseComisionSemanal $base, string $clave): float
    {
        $idIndicador = $this->reglas
            ->where('ClaveIndicador', $clave)
            ->first()?->ID_Indicador;

        if (!$idIndicador) return 0.0;

        $resultado = $resultados->get("{$idIndicador}_0");
        if (!$resultado || $resultado->ValorReal <= 0) return 0.0;

        $regla = $this->buscarRegla($clave, $base->TCE, $base->Puesto, $base->Canal, 0, null);
        if (!$regla || !$regla->Factor) return 0.0;

        $descuento = round((float) $resultado->ValorReal * (float) $regla->Factor, 2);

        // Guardar MontoCalculado en el resultado para trazabilidad
        $resultado->MontoCalculado = $descuento;
        $resultado->save();

        return $descuento;
    }
}
