<?php
namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Empleado extends Model
{
    protected $table = 'core.empleado';
    protected $primaryKey = 'Numero_Empleado';
    public $incrementing = false;
    protected $keyType = 'int';

    const CREATED_AT = 'FechaCreacion';
    const UPDATED_AT = 'FechaModificacion';

    protected $fillable = [
        'Numero_Empleado', 'Nombre', 'Correo', 'Extension', 'Telefono',
        'UsuarioAnyDesk', 'ClaveAnyDesk_Enc',
        'ID_Sucursal', 'ID_Puesto', 'ID_Area', 'Activo',
    ];

    protected $hidden = ['ClaveAnyDesk_Enc'];

    protected $casts = [
        'Activo'            => 'boolean',
        'FechaCreacion'     => 'datetime',
        'FechaModificacion' => 'datetime',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'ID_Sucursal', 'ID_Sucursal');
    }

    public function puesto(): BelongsTo
    {
        return $this->belongsTo(Puesto::class, 'ID_Puesto', 'ID_Puesto');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'ID_Area', 'ID_Area');
    }

    public function usuarioRelacion(): HasOne
    {
        return $this->hasOne(UsuarioRelacion::class, 'Numero_Empleado', 'Numero_Empleado');
    }

    public function equiposAsignados(): HasMany
    {
        return $this->hasMany(EmpleadoEquipo::class, 'Numero_Empleado', 'Numero_Empleado');
    }
}
