<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permiso extends Model
{
    protected $table = 'core.permiso';
    protected $primaryKey = 'ID_Permiso';
    public $timestamps = false;

    protected $fillable = ['Clave', 'Nombre', 'Modulo', 'Descripcion', 'Activo'];
    protected $casts = ['Activo' => 'boolean'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Rol::class,
            'core.rol_permiso',
            'ID_Permiso',
            'ID_Rol'
        )->withPivot(['Activo', 'FechaCreacion', 'AsignadoPor']);
    }
}
