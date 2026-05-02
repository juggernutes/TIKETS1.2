<?php

namespace App\Http\Controllers\Com;

use App\Http\Controllers\Controller;
use App\Models\Com\ReglaComision;
use App\Rules\SqlServerExists;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * USUARIO ADMIN
 * CRUD de reglas de comisión.
 * Las reglas definen cuánto gana un empleado por indicador según su TCE/Canal/Puesto
 * y el rango de porcentaje de cumplimiento alcanzado.
 *
 * Lógica de prioridad en el motor de cálculo:
 *   1. TCE + Puesto + Canal  (más específica)
 *   2. TCE  solo
 *   3. Puesto + Canal
 *   4. Puesto solo
 *   5. Sin filtro  (regla global del indicador)
 */
class ReglaComisionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ReglaComision::with(['indicador', 'subIndicador'])
            ->orderBy('ID_Indicador')
            ->orderBy('TCE')
            ->orderBy('PorcentajeMinimo');

        if ($request->filled('id_indicador')) {
            $query->where('ID_Indicador', $request->integer('id_indicador'));
        }

        if ($request->filled('tce')) {
            $query->where('TCE', strtoupper($request->string('tce')));
        }

        if ($request->filled('canal')) {
            $query->where('Canal', strtoupper($request->string('canal')));
        }

        if ($request->filled('activo')) {
            $query->where('Activo', $request->boolean('activo'));
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ID_Indicador'     => ['required', 'integer', new SqlServerExists('com.indicador', 'ID_Indicador')],
            'ID_SubIndicador'  => ['nullable', 'integer', new SqlServerExists('com.sub_indicador', 'ID_SubIndicador')],
            'TCE'              => 'nullable|string|in:VT,VM,XT,XM',
            'Puesto'           => 'nullable|string|in:VENDEDOR,SUPERVISOR',
            'Canal'            => 'nullable|string|in:TRADICIONAL,MODERNO',
            'PorcentajeMinimo' => 'required|numeric|min:0',
            'PorcentajeMaximo' => 'required|numeric|min:0|gte:PorcentajeMinimo',
            'Monto'            => 'required|numeric|min:0',
            'Factor'           => 'nullable|numeric|min:0',
            'ID_UsuarioCreo'   => ['nullable', 'integer', new SqlServerExists('core.usuario', 'ID_Usuario')],
        ]);

        $data['Activo'] = true;

        $regla = ReglaComision::create($data);

        $this->registrarHistorial($regla, 'INSERT', $data['ID_UsuarioCreo'] ?? null);

        return response()->json([
            'message' => 'Regla creada.',
            'data'    => $regla->load(['indicador', 'subIndicador']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $regla = ReglaComision::with(['indicador', 'subIndicador'])->findOrFail($id);

        $historial = DB::table('com.regla_comision_historial')
            ->where('ID_Regla', $id)
            ->orderByDesc('Fecha')
            ->limit(20)
            ->get();

        return response()->json([
            'regla'    => $regla,
            'historial'=> $historial,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $regla = ReglaComision::findOrFail($id);

        $data = $request->validate([
            'TCE'              => 'nullable|string|in:VT,VM,XT,XM',
            'Puesto'           => 'nullable|string|in:VENDEDOR,SUPERVISOR',
            'Canal'            => 'nullable|string|in:TRADICIONAL,MODERNO',
            'PorcentajeMinimo' => 'sometimes|numeric|min:0',
            'PorcentajeMaximo' => 'sometimes|numeric|min:0',
            'Monto'            => 'sometimes|numeric|min:0',
            'Factor'           => 'nullable|numeric|min:0',
            'Activo'           => 'sometimes|boolean',
            'ID_Usuario'       => ['nullable', 'integer', new SqlServerExists('core.usuario', 'ID_Usuario')],
        ]);

        // Validar rango si se envían ambos extremos
        $minimo = $data['PorcentajeMinimo'] ?? $regla->PorcentajeMinimo;
        $maximo = $data['PorcentajeMaximo'] ?? $regla->PorcentajeMaximo;

        if ($maximo < $minimo) {
            return response()->json([
                'message' => 'PorcentajeMaximo debe ser mayor o igual a PorcentajeMinimo.',
            ], 422);
        }

        $idUsuario = $data['ID_Usuario'] ?? null;
        unset($data['ID_Usuario']);

        $regla->fill($data)->save();

        $this->registrarHistorial($regla, 'UPDATE', $idUsuario);

        return response()->json([
            'message' => 'Regla actualizada.',
            'data'    => $regla->fresh(['indicador', 'subIndicador']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $regla = ReglaComision::findOrFail($id);

        // Soft-delete: marcar inactiva en lugar de borrar físicamente
        $regla->Activo = false;
        $regla->save();

        $this->registrarHistorial($regla, 'DELETE', null);

        return response()->json(['message' => 'Regla desactivada.']);
    }

    /**
     * Carga masiva de reglas desde array.
     * Útil para importar la tabla de comisiones desde Excel.
     */
    public function storeBulk(Request $request): JsonResponse
    {
        $request->validate([
            'reglas'                    => 'required|array|min:1',
            'reglas.*.ID_Indicador'     => ['required', 'integer', new SqlServerExists('com.indicador', 'ID_Indicador')],
            'reglas.*.ID_SubIndicador'  => ['nullable', 'integer', new SqlServerExists('com.sub_indicador', 'ID_SubIndicador')],
            'reglas.*.TCE'              => 'nullable|string|in:VT,VM,XT,XM',
            'reglas.*.Puesto'           => 'nullable|string|in:VENDEDOR,SUPERVISOR',
            'reglas.*.Canal'            => 'nullable|string|in:TRADICIONAL,MODERNO',
            'reglas.*.PorcentajeMinimo' => 'required|numeric|min:0',
            'reglas.*.PorcentajeMaximo' => 'required|numeric|min:0',
            'reglas.*.Monto'            => 'required|numeric|min:0',
            'reglas.*.Factor'           => 'nullable|numeric|min:0',
            'ID_UsuarioCreo'            => ['nullable', 'integer', new SqlServerExists('core.usuario', 'ID_Usuario')],
        ]);

        $idUsuario = $request->integer('ID_UsuarioCreo') ?: null;
        $creadas   = 0;
        $errores   = [];

        foreach ($request->input('reglas') as $i => $item) {
            if (($item['PorcentajeMaximo'] ?? 0) < ($item['PorcentajeMinimo'] ?? 0)) {
                $errores[] = "Fila {$i}: PorcentajeMaximo < PorcentajeMinimo.";
                continue;
            }

            $regla = ReglaComision::create(array_merge($item, [
                'Activo'        => true,
                'ID_UsuarioCreo'=> $idUsuario,
            ]));

            $this->registrarHistorial($regla, 'INSERT', $idUsuario);
            $creadas++;
        }

        return response()->json([
            'message' => "{$creadas} regla(s) creadas.",
            'creadas' => $creadas,
            'errores' => $errores,
        ], empty($errores) ? 201 : 207);
    }

    private function registrarHistorial(ReglaComision $regla, string $accion, ?int $idUsuario): void
    {
        DB::table('com.regla_comision_historial')->insert([
            'ID_Regla'         => $regla->ID_Regla,
            'PorcentajeMinimo' => $regla->PorcentajeMinimo,
            'PorcentajeMaximo' => $regla->PorcentajeMaximo,
            'Monto'            => $regla->Monto,
            'Factor'           => $regla->Factor,
            'Activo'           => $regla->Activo,
            'Accion'           => $accion,
            'ID_Usuario'       => $idUsuario,
        ]);
    }
}
