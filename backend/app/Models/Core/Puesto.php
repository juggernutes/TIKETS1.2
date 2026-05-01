<?php
namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Puesto extends Model
{
    protected $table = 'core.puesto';
    protected $primaryKey = 'ID_Puesto';
    public $timestamps = false;

    protected $fillable = ['Clave', 'Descripcion', 'Nivel', 'Categoria', 'Segmento', 'Responsabilidad', 'Activo'];
    protected $casts = ['Activo' => 'boolean', 'Nivel' => 'integer'];

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'ID_Puesto', 'ID_Puesto');
    }
}
