<?php
namespace App\Models\Rh;

use Illuminate\Database\Eloquent\Model;

class EstatusCandidato extends Model
{
    protected $table = 'rh.estatus_candidato';
    protected $primaryKey = 'ID_EstatusCandidato';

    const CREATED_AT = 'FechaCreacion';
    const UPDATED_AT = 'FechaActualizacion';

    protected $fillable = ['Nombre', 'Descripcion', 'OrdenProceso', 'Activo'];
    protected $casts = ['Activo' => 'boolean'];
}
