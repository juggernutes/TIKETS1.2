<?php
namespace App\Models\Ped;

use App\Models\Com\Semana;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    protected $table = 'ped.pedidos';
    protected $primaryKey = 'IdPedido';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'FolioPedido', 'IdEstado', 'IdUnidadPedido', 'IdSupervisor', 'IdAlmacen',
        'Registros', 'Dia', 'Semana', 'ID_Semana',
        'PedVolPed', 'PedVolApr', 'PedVolSur',
        'FechaPedido', 'FechaAutorizacion', 'FechaSurtido',
        'ObserVen', 'ObserSup', 'ObserAlm', 'activo',
    ];

    protected $casts = [
        'activo'           => 'boolean',
        'FechaPedido'      => 'datetime',
        'FechaAutorizacion'=> 'datetime',
        'FechaSurtido'     => 'datetime',
        'PedVolPed'        => 'decimal:3',
        'PedVolApr'        => 'decimal:3',
        'PedVolSur'        => 'decimal:3',
        'Registros'        => 'integer',
        'Semana'           => 'integer',
    ];

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoPedido::class, 'IdEstado', 'IdEstado');
    }

    public function unidadPedido(): BelongsTo
    {
        return $this->belongsTo(UnidadOperacional::class, 'IdUnidadPedido', 'IdUnidad');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(UnidadOperacional::class, 'IdSupervisor', 'IdUnidad');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(UnidadOperacional::class, 'IdAlmacen', 'IdUnidad');
    }

    public function semana(): BelongsTo
    {
        return $this->belongsTo(Semana::class, 'ID_Semana', 'ID_Semana');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(PedidoDetalle::class, 'IdPedido', 'IdPedido');
    }

    public function estadoLog(): HasMany
    {
        return $this->hasMany(PedidoEstadoLog::class, 'IdPedido', 'IdPedido');
    }
}
