<?php
namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoEquipo extends Model
{
    protected $table = 'core.tipo_equipo';
    protected $primaryKey = 'ID_TipoEquipo';
    public $timestamps = false;

    protected $fillable = ['Nombre', 'Descripcion', 'Activo'];

    protected $casts = ['Activo' => 'boolean'];

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'ID_TipoEquipo', 'ID_TipoEquipo');
    }
}
