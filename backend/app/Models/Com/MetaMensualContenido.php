<?php
namespace App\Models\Com;

use App\Models\Cat\LineaArticulo;
use App\Models\Ped\UnidadOperacional;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaMensualContenido extends Model
{
    protected $table = 'com.meta_mensual_contenido';
    protected $primaryKey = 'ID_Meta';
    public $timestamps = false;

    protected $fillable = [
        'ID_MetaMes', 'IdUnidad', 'IdLineaArticulo',
        'Meta', 'Porcentaje', 'Mezcla',
        'ID_UsuarioCreo', 'ID_UsuarioModifico',
    ];

    protected $casts = [
        'Meta'              => 'decimal:4',
        'Porcentaje'        => 'decimal:4',
        'Mezcla'            => 'decimal:4',
        'FechaCreacion'     => 'datetime',
        'FechaModificacion' => 'datetime',
    ];

    public function portada(): BelongsTo
    {
        return $this->belongsTo(MetaMesPortada::class, 'ID_MetaMes', 'ID_MetaMes');
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(UnidadOperacional::class, 'IdUnidad', 'IdUnidad');
    }

    public function lineaArticulo(): BelongsTo
    {
        return $this->belongsTo(LineaArticulo::class, 'IdLineaArticulo', 'IdLineaArticulo');
    }
}
