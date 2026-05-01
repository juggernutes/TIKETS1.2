<?php
namespace App\Models\Cat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LineaArticulo extends Model
{
    protected $table = 'cat.linea_articulo';
    protected $primaryKey = 'IdLineaArticulo';

    protected $fillable = ['Nombre', 'Descripcion', 'Activo'];
    protected $casts    = ['Activo' => 'boolean'];

    public function grupos(): HasMany
    {
        return $this->hasMany(GrupoArticulo::class, 'IdLineaArticulo', 'IdLineaArticulo');
    }
}
