<?php
namespace App\Models\Hd;

use Illuminate\Database\Eloquent\Model;

class EstatusHd extends Model
{
    protected $table = 'hd.estatus';
    protected $primaryKey = 'ID_Estatus';
    public $timestamps = false;

    protected $fillable = ['Nombre', 'Orden'];
}
