<?php
namespace App\Models\Hd;

use Illuminate\Database\Eloquent\Model;

class Solucion extends Model
{
    protected $table = 'hd.solucion';
    protected $primaryKey = 'ID_Solucion';
    public $timestamps = false;

    protected $fillable = ['Descripcion', 'Activo'];
    protected $casts = ['Activo' => 'boolean'];
}
