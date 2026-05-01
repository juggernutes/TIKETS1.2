<?php
namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpleadoEquipo extends Model
{
    protected $table = 'core.empleado_equipo';
    protected $primaryKey = 'ID_EmpleadoEquipo';
    public $timestamps = false;

    protected $fillable = [
        'Numero_Empleado', 'ID_Equipo', 'FechaAsignacion',
        'FechaDevolucion', 'Observaciones', 'Activo',
    ];

    protected $casts = [
        'Activo'           => 'boolean',
        'FechaAsignacion'  => 'datetime',
        'FechaDevolucion'  => 'datetime',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'Numero_Empleado', 'Numero_Empleado');
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'ID_Equipo', 'ID_Equipo');
    }
}
