<?php
namespace App\Models\Com;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Indicador extends Model
{
    protected $table = 'com.indicador';
    protected $primaryKey = 'ID_Indicador';
    public $timestamps = false;

    protected $fillable = ['Clave', 'Nombre', 'Categoria', 'OrdenResumen', 'Activo'];
    protected $casts    = ['Activo' => 'boolean'];

    public function subIndicadores(): HasMany
    {
        return $this->hasMany(SubIndicador::class, 'ID_Indicador', 'ID_Indicador');
    }

    public function reglas(): HasMany
    {
        return $this->hasMany(ReglaComision::class, 'ID_Indicador', 'ID_Indicador');
    }
}
