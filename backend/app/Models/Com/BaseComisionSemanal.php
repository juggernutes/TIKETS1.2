<?php
namespace App\Models\Com;

use App\Models\Core\Empleado;
use App\Models\Core\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BaseComisionSemanal extends Model
{
    protected $table = 'com.base_comision_semanal';
    protected $primaryKey = 'ID_Base';
    public $timestamps = false;

    protected $fillable = [
        'ID_Corrida', 'Numero_Empleado', 'Sucursal', 'NombreEmpleado',
        'Ruta', 'Puesto', 'Canal', 'TCE', 'Activo', 'ID_UsuarioCreo',
    ];

    protected $casts = [
        'Activo'        => 'boolean',
        'FechaCreacion' => 'datetime',
    ];

    public function corrida(): BelongsTo
    {
        return $this->belongsTo(CorridaComision::class, 'ID_Corrida', 'ID_Corrida');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'Numero_Empleado', 'Numero_Empleado');
    }

    public function resultadosIndicador(): HasMany
    {
        return $this->hasMany(ResultadoIndicador::class, 'ID_Base', 'ID_Base');
    }

    public function resultadosDoc(): HasMany
    {
        return $this->hasMany(ResultadoDoc::class, 'ID_Base', 'ID_Base');
    }

    public function ajustes(): HasMany
    {
        return $this->hasMany(AjusteComision::class, 'ID_Base', 'ID_Base');
    }

    public function calculo(): HasOne
    {
        return $this->hasOne(CalculoComision::class, 'ID_Base', 'ID_Base');
    }
}
