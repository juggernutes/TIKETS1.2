<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\Empleado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmpleadoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Empleado::with(['sucursal', 'puesto', 'area'])
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $term = '%' . $request->buscar . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('Nombre', 'like', $term)
                          ->orWhere('Correo', 'like', $term)
                          ->orWhere('Numero_Empleado', 'like', $term);
                });
            })
            ->when($request->filled('area'), fn ($q) => $q->where('ID_Area', $request->area))
            ->when($request->filled('sucursal'), fn ($q) => $q->where('ID_Sucursal', $request->sucursal))
            ->when($request->filled('activo'), fn ($q) => $q->where('Activo', (bool) $request->activo))
            ->orderBy('Nombre');

        $perPage = min((int) ($request->per_page ?? 20), 100);

        $empleados = $query->paginate($perPage)->through(fn ($e) => [
            'numero_empleado' => $e->Numero_Empleado,
            'nombre'          => $e->Nombre,
            'correo'          => $e->Correo,
            'extension'       => $e->Extension,
            'telefono'        => $e->Telefono,
            'usuario_anydesk' => $e->UsuarioAnyDesk,
            'activo'          => $e->Activo,
            'sucursal'        => $e->sucursal?->Nombre,
            'puesto'          => $e->puesto?->Descripcion,
            'area'            => $e->area?->Nombre,
        ]);

        return response()->json($empleados);
    }

    public function show(int $numeroEmpleado): JsonResponse
    {
        $empleado = Empleado::with([
            'sucursal',
            'puesto',
            'area',
            'usuarioRelacion.usuario.rol',
            'equiposAsignados.equipo.tipo',
        ])->findOrFail($numeroEmpleado);

        return response()->json($empleado);
    }

    public function equipos(int $numeroEmpleado): JsonResponse
    {
        $empleado = Empleado::findOrFail($numeroEmpleado);

        $equipos = $empleado->equiposAsignados()
            ->with('equipo.tipo')
            ->orderByDesc('Activo')
            ->orderByDesc('FechaAsignacion')
            ->get();

        return response()->json($equipos);
    }
}
