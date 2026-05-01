<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Gestión de adjuntos genéricos para cualquier módulo.
 * core.adjunto almacena solo los metadatos; el archivo va al disco.
 */
class AdjuntoController extends Controller
{
    private const MODULOS = ['hd', 'rh', 'ped', 'com', 'core'];
    private const MAX_MB   = 10;
    private const EXTENSIONES = [
        'jpg', 'jpeg', 'png', 'webp', 'gif',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt',
    ];

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'modulo'   => 'required|string|in:hd,rh,ped,com,core',
            'entidad'  => 'required|string|max:50',
            'id_ref'   => 'required|integer',
        ]);

        $adjuntos = DB::table('core.adjunto')
            ->where('Modulo',       $request->string('modulo'))
            ->where('Entidad',      $request->string('entidad'))
            ->where('ID_Referencia',$request->integer('id_ref'))
            ->where('Activo',        1)
            ->orderByDesc('FechaCreacion')
            ->get();

        return response()->json($adjuntos);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'Modulo'         => 'required|string|in:hd,rh,ped,com,core',
            'Entidad'        => 'required|string|max:50',
            'ID_Referencia'  => 'required|integer',
            'archivo'        => 'required|file|max:' . (self::MAX_MB * 1024),
            'Descripcion'    => 'nullable|string|max:200',
            'ID_UsuarioCreo' => 'nullable|integer',
        ]);

        if (!empty($data['ID_UsuarioCreo']) && !DB::table('core.usuario')->where('ID_Usuario', (int) $data['ID_UsuarioCreo'])->exists()) {
            return response()->json(['message' => 'Usuario creador no valido.'], 422);
        }

        $file      = $request->file('archivo');
        $extension = strtolower($file->getClientOriginalExtension());
        $mime      = $file->getMimeType();
        $tamano    = $file->getSize();
        $nombre    = $file->getClientOriginalName();

        if (!in_array($extension, self::EXTENSIONES, true)) {
            return response()->json(['message' => 'Tipo de archivo no permitido.'], 422);
        }

        $nombreBase = Str::slug(pathinfo($nombre, PATHINFO_FILENAME)) ?: 'archivo';
        $nombreFisico = now()->format('YmdHis') . '_' . Str::uuid() . '_' . $nombreBase . '.' . $extension;

        // Ruta: modulo/entidad/id_ref/timestamp_uuid_nombre
        $carpeta = "{$data['Modulo']}/{$data['Entidad']}/{$data['ID_Referencia']}";
        $ruta    = $file->storeAs($carpeta, $nombreFisico, 'local');

        $id = DB::table('core.adjunto')->insertGetId([
            'Modulo'         => $data['Modulo'],
            'Entidad'        => $data['Entidad'],
            'ID_Referencia'  => $data['ID_Referencia'],
            'NombreArchivo'  => $nombre,
            'RutaArchivo'    => $ruta,
            'Extension'      => $extension,
            'TamanoBytes'    => $tamano,
            'MimeType'       => $mime,
            'Descripcion'    => $data['Descripcion'] ?? null,
            'Activo'         => 1,
            'ID_UsuarioCreo' => $data['ID_UsuarioCreo'] ?? null,
        ]);

        return response()->json([
            'message' => 'Archivo adjuntado.',
            'data'    => DB::table('core.adjunto')->where('ID_Adjunto', $id)->first(),
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $adjunto = DB::table('core.adjunto')->where('ID_Adjunto', $id)->first();
        if (!$adjunto) {
            return response()->json(['message' => 'Adjunto no encontrado.'], 404);
        }

        // Soft-delete + borrar archivo físico
        DB::table('core.adjunto')->where('ID_Adjunto', $id)->update(['Activo' => false]);

        if ($adjunto->RutaArchivo && Storage::disk('local')->exists($adjunto->RutaArchivo)) {
            Storage::disk('local')->delete($adjunto->RutaArchivo);
        }

        return response()->json(['message' => 'Adjunto eliminado.']);
    }

    public function download(int $id)
    {
        $adjunto = DB::table('core.adjunto')->where('ID_Adjunto', $id)->where('Activo', 1)->first();
        if (!$adjunto) {
            return response()->json(['message' => 'Adjunto no encontrado.'], 404);
        }

        if (!$adjunto->RutaArchivo || !Storage::disk('local')->exists($adjunto->RutaArchivo)) {
            return response()->json(['message' => 'Archivo no disponible en el servidor.'], 404);
        }

        return Storage::disk('local')->download($adjunto->RutaArchivo, $adjunto->NombreArchivo);
    }
}
