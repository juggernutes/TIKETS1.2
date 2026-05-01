<?php
namespace App\Models\Ped;

use App\Models\Cat\Articulo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoDetalle extends Model
{
    protected $table = 'ped.pedido_detalle';
    protected $primaryKey = null;
    public $incrementing = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'IdPedido', 'IdArticulo', 'Registro',
        'CanPzPed', 'VolPed', 'CanPzApr', 'VolApr',
        'CanPzSur', 'VolSur', 'activo',
    ];

    protected $casts = [
        'activo'   => 'boolean',
        'VolPed'   => 'decimal:3',
        'VolApr'   => 'decimal:3',
        'VolSur'   => 'decimal:3',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'IdPedido', 'IdPedido');
    }

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class, 'IdArticulo', 'IdArticulo');
    }
}
