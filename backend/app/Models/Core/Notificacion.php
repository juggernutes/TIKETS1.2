<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    protected $table      = 'core.notificacion';
    protected $primaryKey = 'ID_Notificacion';

    const CREATED_AT = 'FechaCreacion';
    const UPDATED_AT = null;

    protected $fillable = [
        'ID_Usuario',
        'Tipo',
        'Modulo',
        'ID_Referencia',
        'Titulo',
        'Mensaje',
        'Leida',
        'FechaLeida',
        'FechaExpiracion',
    ];

    protected $casts = [
        'Leida'           => 'boolean',
        'FechaLeida'      => 'datetime',
        'FechaCreacion'   => 'datetime',
        'FechaExpiracion' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_Usuario', 'ID_Usuario');
    }

    // ── Helpers de creación ───────────────────────────────────────────────

    public static function enviar(
        int    $idUsuario,
        string $tipo,
        string $modulo,
        string $titulo,
        string $mensaje = null,
        int    $idReferencia = null,
    ): self {
        return static::create([
            'ID_Usuario'    => $idUsuario,
            'Tipo'          => $tipo,
            'Modulo'        => $modulo,
            'ID_Referencia' => $idReferencia,
            'Titulo'        => $titulo,
            'Mensaje'       => $mensaje,
        ]);
    }

    public static function enviarAVarios(
        array  $idsUsuario,
        string $tipo,
        string $modulo,
        string $titulo,
        string $mensaje = null,
        int    $idReferencia = null,
    ): void {
        $now  = now();
        $rows = array_map(fn (int $id) => [
            'ID_Usuario'    => $id,
            'Tipo'          => $tipo,
            'Modulo'        => $modulo,
            'ID_Referencia' => $idReferencia,
            'Titulo'        => $titulo,
            'Mensaje'       => $mensaje,
            'Leida'         => false,
            'FechaCreacion' => $now,
        ], $idsUsuario);

        static::insert($rows);
    }
}
