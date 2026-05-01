<?php
namespace App\Models\Com;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglaComision extends Model
{
    protected $table = 'com.regla_comision';
    protected $primaryKey = 'ID_Regla';
    public $timestamps = false;

    protected $fillable = [
        'ID_Indicador', 'ID_SubIndicador', 'TCE', 'Puesto', 'Canal',
        'PorcentajeMinimo', 'PorcentajeMaximo', 'Monto', 'Factor', 'Activo',
    ];

    protected $casts = [
        'Activo'           => 'boolean',
        'PorcentajeMinimo' => 'decimal:6',
        'PorcentajeMaximo' => 'decimal:6',
        'Monto'            => 'decimal:2',
        'Factor'           => 'decimal:6',
        'FechaCreacion'    => 'datetime',
        'FechaModificacion'=> 'datetime',
    ];

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class, 'ID_Indicador', 'ID_Indicador');
    }

    public function subIndicador(): BelongsTo
    {
        return $this->belongsTo(SubIndicador::class, 'ID_SubIndicador', 'ID_SubIndicador');
    }
}
