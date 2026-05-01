<?php
namespace App\Models\Ped;

use Illuminate\Database\Eloquent\Model;

class EstadoPedido extends Model
{
    protected $table = 'ped.estado_pedido';
    protected $primaryKey = 'IdEstado';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = ['Nombre', 'Descripcion', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
