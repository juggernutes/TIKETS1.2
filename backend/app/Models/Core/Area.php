<?php
namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $table = 'core.area';
    protected $primaryKey = 'ID_Area';
    public $timestamps = false;

    protected $fillable = ['Nombre', 'Serie', 'Activo'];
    protected $casts = ['Activo' => 'boolean'];

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'ID_Area', 'ID_Area');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'ID_Area', 'ID_Area');
    }
}
