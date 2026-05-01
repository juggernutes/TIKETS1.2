<?php

namespace App\Http\Controllers\Com;

use App\Http\Controllers\Controller;
use App\Models\Com\MetaMensualContenido;
use App\Models\Com\MetaMesPortada;
use App\Models\Com\MetaSemanal;
use App\Models\Com\Semana;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * GERENTES DE SUCURSAL
 * Capturan portada del mes (días hábiles) y metas por unidad/línea de artículo.
 * También disparan el cálculo de metas semanales proporcionales.
 */
class MetaMensualController extends Controller
{
    // ── Portadas ─────────────────────────────────────────────────────────────

    public function indexPortadas(Request $request): JsonResponse
    {
        $query = MetaMesPortada::with('usuarioCreo')
            ->orderByDesc('Anio')
            ->orderByDesc('Mes');

        if ($request->filled('anio')) {
            $query->where('Anio', $request->integer('anio'));
        }

        return response()->json($query->get());
    }

    public function storePortada(Request $request): JsonResponse
    {
        $data = $request->validate([
            'Anio'            => 'required|integer|min:2000',
            'Mes'             => 'required|integer|between:1,12',
            'Nombre'          => 'required|string|max:15',
            'DiasHabiles'     => 'required|integer|between:1,31',
            'ID_UsuarioCreo'  => 'nullable|integer|exists:core.usuario,ID_Usuario',
        ]);

        $portada = MetaMesPortada::create($data);

        return response()->json([
            'message' => 'Portada mensual creada.',
            'data'    => $portada,
        ], 201);
    }

    public function showPortada(int $id): JsonResponse
    {
        $portada = MetaMesPortada::with(['contenido.unidad', 'contenido.lineaArticulo'])
            ->findOrFail($id);

        return response()->json($portada);
    }

    public function updatePortada(Request $request, int $id): JsonResponse
    {
        $portada = MetaMesPortada::findOrFail($id);

        $data = $request->validate([
            'Nombre'               => 'sometimes|string|max:15',
            'DiasHabiles'          => 'sometimes|integer|between:1,31',
            'ID_UsuarioModifico'   => 'nullable|integer|exists:core.usuario,ID_Usuario',
        ]);

        $data['FechaModificacion'] = now();
        $portada->update($data);

        return response()->json([
            'message' => 'Portada actualizada.',
            'data'    => $portada->fresh(),
        ]);
    }

    // ── Contenido (metas por unidad + línea) ─────────────────────────────────

    /**
     * Upsert masivo de metas para un mes.
     * El gerente envía el arreglo completo de unidades × líneas.
     */
    public function storeMetas(Request $request, int $idMes): JsonResponse
    {
        MetaMesPortada::findOrFail($idMes);

        $request->validate([
            'metas'                          => 'required|array|min:1',
            'metas.*.IdUnidad'               => 'required|integer|exists:ped.unidadoperacional,IdUnidad',
            'metas.*.IdLineaArticulo'        => 'required|integer|exists:cat.linea_articulo,IdLineaArticulo',
            'metas.*.Meta'                   => 'required|numeric|min:0',
            'metas.*.Porcentaje'             => 'required|numeric|min:0',
            'metas.*.Mezcla'                 => 'required|numeric|min:0',
            'ID_UsuarioCreo'                 => 'nullable|integer|exists:core.usuario,ID_Usuario',
        ]);

        $idUsuario    = $request->integer('ID_UsuarioCreo');
        $creados      = 0;
        $actualizados = 0;

        foreach ($request->input('metas') as $item) {
            $existe = MetaMensualContenido::where('ID_MetaMes', $idMes)
                ->where('IdUnidad', $item['IdUnidad'])
                ->where('IdLineaArticulo', $item['IdLineaArticulo'])
                ->first();

            if ($existe) {
                $existe->update([
                    'Meta'               => $item['Meta'],
                    'Porcentaje'         => $item['Porcentaje'],
                    'Mezcla'             => $item['Mezcla'],
                    'ID_UsuarioModifico' => $idUsuario ?: null,
                    'FechaModificacion'  => now(),
                ]);
                $actualizados++;
            } else {
                MetaMensualContenido::create([
                    'ID_MetaMes'      => $idMes,
                    'IdUnidad'        => $item['IdUnidad'],
                    'IdLineaArticulo' => $item['IdLineaArticulo'],
                    'Meta'            => $item['Meta'],
                    'Porcentaje'      => $item['Porcentaje'],
                    'Mezcla'          => $item['Mezcla'],
                    'ID_UsuarioCreo'  => $idUsuario ?: null,
                ]);
                $creados++;
            }
        }

        return response()->json([
            'message'      => 'Metas guardadas.',
            'creados'      => $creados,
            'actualizados' => $actualizados,
        ]);
    }

    /**
     * Calcula y guarda las metas semanales proporcionales a partir de una portada mensual.
     * Regla: MetaSemanal = Meta * (DiasMes / DiasHabiles)
     * donde DiasMes es DiasMesInicio o DiasMesFinal según corresponda.
     */
    public function calcularMetasSemanales(Request $request, int $idMes): JsonResponse
    {
        $portada = MetaMesPortada::findOrFail($idMes);

        $semanas = Semana::where(function ($q) use ($idMes) {
            $q->where('ID_MetaMesInicio', $idMes)
              ->orWhere('ID_MetaMesFinal', $idMes);
        })->get();

        if ($semanas->isEmpty()) {
            return response()->json(['message' => 'No hay semanas asociadas a este mes.'], 422);
        }

        $metas   = MetaMensualContenido::where('ID_MetaMes', $idMes)->get();
        $creados = 0;

        foreach ($semanas as $semana) {
            // Días del mes en esta semana
            $diasMes = ($semana->ID_MetaMesInicio === $idMes)
                ? $semana->DiasMesInicio
                : $semana->DiasMesFinal;

            $factor = $portada->DiasHabiles > 0
                ? $diasMes / $portada->DiasHabiles
                : 0;

            foreach ($metas as $meta) {
                MetaSemanal::updateOrCreate(
                    [
                        'ID_Semana'       => $semana->ID_Semana,
                        'IdUnidad'        => $meta->IdUnidad,
                        'IdLineaArticulo' => $meta->IdLineaArticulo,
                    ],
                    ['MetaSemanal' => round($meta->Meta * $factor, 4)]
                );
                $creados++;
            }
        }

        return response()->json([
            'message'  => 'Metas semanales calculadas.',
            'semanas'  => $semanas->count(),
            'registros'=> $creados,
        ]);
    }
}
