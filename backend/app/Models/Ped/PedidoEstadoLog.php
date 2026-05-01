<?php
namespace App\Models\Ped;

use App\Models\Core\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoEstadoLog extends Model
{
    protected $table = 'ped.pedido_estado_log';
    protected $primaryKey = 'IdLog';
    public $timestamps = false;

    protected $fillable = ['IdPedido', 'IdEstado', 'CambioPor', 'Notas', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'IdPedido', 'IdPedido');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoPedido::class, 'IdEstado', 'IdEstado');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'CambioPor', 'ID_Usuario');
    }
}
