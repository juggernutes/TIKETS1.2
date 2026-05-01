<?php
namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipo extends Model
{
    protected $table = 'core.equipo';
    protected $primaryKey = 'ID_Equipo';

    const CREATED_AT = 'FechaCreacion';
    const UPDATED_AT = null;

    protected $fillable = [
        'ID_TipoEquipo', 'Marca', 'Modelo', 'NumeroSerie', 'IPDireccion',
        'MacDireccion', 'NuActvoFijo', 'SistemaOperativo', 'Descripcion',
        'FechaCompra', 'ClaveUsuarioWindows_Enc', 'Activo',
    ];

    protected $hidden = ['ClaveUsuarioWindows_Enc'];

    protected $casts = [
        'Activo'        => 'boolean',
        'FechaCompra'   => 'date',
        'FechaCreacion' => 'datetime',
    ];

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoEquipo::class, 'ID_TipoEquipo', 'ID_TipoEquipo');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(EmpleadoEquipo::class, 'ID_Equipo', 'ID_Equipo');
    }
}
