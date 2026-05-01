<?php
namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    protected $table = 'core.rol';
    protected $primaryKey = 'ID_Rol';
    public $timestamps = false;

    protected $fillable = ['Nombre', 'Activo'];
    protected $casts = ['Activo' => 'boolean'];

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'ID_Rol', 'ID_Rol');
    }

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(
            Permiso::class,
            'core.rol_permiso',
            'ID_Rol',
            'ID_Permiso'
        )->wherePivot('Activo', true)
            ->where('core.permiso.Activo', true)
            ->withPivot(['Activo', 'FechaCreacion', 'AsignadoPor']);
    }
}
