<?php
namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    protected $table = 'core.sucursal';
    protected $primaryKey = 'ID_Sucursal';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['ID_Sucursal', 'Nombre', 'Ciudad', 'Activo'];
    protected $casts = ['Activo' => 'boolean'];

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'ID_Sucursal', 'ID_Sucursal');
    }
}
