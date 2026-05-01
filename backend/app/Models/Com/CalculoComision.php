<?php
namespace App\Models\Com;

use App\Models\Core\Empleado;
use App\Models\Core\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalculoComision extends Model
{
    protected $table = 'com.calculo_comision';
    protected $primaryKey = 'ID_Calculo';
    public $timestamps = false;

    protected $fillable = [
        'ID_Base', 'MontoBruto', 'TotalDescuentos', 'TotalAgregados',
        'MontoFinal', 'Estatus', 'CalculadoPor',
        'Aprobado', 'FechaAprobacion', 'AprobadoPor', 'Observaciones',
    ];

    protected $casts = [
        'MontoBruto'       => 'decimal:2',
        'TotalDescuentos'  => 'decimal:2',
        'TotalAgregados'   => 'decimal:2',
        'MontoFinal'       => 'decimal:2',
        'Aprobado'         => 'boolean',
        'FechaCalculo'     => 'datetime',
        'FechaAprobacion'  => 'datetime',
    ];

    public function base(): BelongsTo
    {
        return $this->belongsTo(BaseComisionSemanal::class, 'ID_Base', 'ID_Base');
    }

    public function calculadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'CalculadoPor', 'ID_Usuario');
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'AprobadoPor', 'ID_Usuario');
    }
}
