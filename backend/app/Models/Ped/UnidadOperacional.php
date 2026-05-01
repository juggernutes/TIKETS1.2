<?php
namespace App\Models\Ped;

use App\Models\Core\Sucursal;
use App\Models\Core\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadOperacional extends Model
{
    protected $table = 'ped.unidadoperacional';
    protected $primaryKey = 'IdUnidad';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'IdTipoUnidad', 'IdUsuario', 'IdSupervisor',
        'IdSucursal', 'IdCapacidadUV', 'Nombre',
        'Descripcion', 'activo',
    ];

    protected $casts = ['activo' => 'boolean'];

    public function tipoUnidad(): BelongsTo
    {
        return $this->belongsTo(TipoUnidad::class, 'IdTipoUnidad', 'IdTipoUnidad');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'IdUsuario', 'ID_Usuario');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'IdSucursal', 'ID_Sucursal');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'IdSupervisor', 'IdUnidad');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'IdUnidadPedido', 'IdUnidad');
    }
}
