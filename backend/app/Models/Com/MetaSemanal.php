<?php
namespace App\Models\Com;

use App\Models\Cat\LineaArticulo;
use App\Models\Ped\UnidadOperacional;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaSemanal extends Model
{
    protected $table = 'com.meta_semanal';
    protected $primaryKey = 'ID_MetaSemanal';
    public $timestamps = false;

    protected $fillable = [
        'ID_Semana', 'IdUnidad', 'IdLineaArticulo', 'MetaSemanal',
    ];

    protected $casts = [
        'MetaSemanal'   => 'decimal:4',
        'FechaCalculo'  => 'datetime',
    ];

    public function semana(): BelongsTo
    {
        return $this->belongsTo(Semana::class, 'ID_Semana', 'ID_Semana');
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
