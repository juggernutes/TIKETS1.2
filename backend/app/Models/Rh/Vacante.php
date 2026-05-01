<?php
namespace App\Models\Rh;

use App\Models\Core\Area;
use App\Models\Core\Puesto;
use App\Models\Core\Sucursal;
use App\Models\Core\Usuario;
use App\Enums\RhEstatusVacante;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Vacante extends Model
{
    protected $table = 'rh.vacante';
    protected $primaryKey = 'ID_Vacante';

    const CREATED_AT = 'FechaCreacion';
    const UPDATED_AT = 'FechaModificacion';

    protected $fillable = [
        'Folio', 'Titulo', 'Descripcion', 'Perfil', 'Requisitos',
        'SalarioMin', 'SalarioMax', 'NumeroPosiciones',
        'FechaPublicacion', 'FechaCierre',
        'ID_Area', 'ID_Puesto', 'ID_Sucursal',
        'ID_UsuarioSolicita', 'ID_UsuarioResponsable',
        'Estatus', 'Activo',
    ];

    protected $casts = [
        'Activo'           => 'boolean',
        'SalarioMin'       => 'decimal:2',
        'SalarioMax'       => 'decimal:2',
        'NumeroPosiciones' => 'integer',
        'FechaPublicacion' => 'datetime',
        'FechaCierre'      => 'datetime',
        'FechaCreacion'    => 'datetime',
        'FechaModificacion'=> 'datetime',
        'Estatus'          => RhEstatusVacante::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $v): void {
            if (empty($v->Folio)) {
                $anio  = Carbon::now()->format('Y');
                $mes   = Carbon::now()->format('m');
                $count = static::whereYear('FechaCreacion', $anio)->count() + 1;
                $v->Folio = 'VAC-' . $anio . $mes . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'ID_Area', 'ID_Area');
    }

    public function puesto(): BelongsTo
    {
        return $this->belongsTo(Puesto::class, 'ID_Puesto', 'ID_Puesto');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'ID_Sucursal', 'ID_Sucursal');
    }

    public function usuarioSolicita(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_UsuarioSolicita', 'ID_Usuario');
    }

    public function usuarioResponsable(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_UsuarioResponsable', 'ID_Usuario');
    }

    public function candidatos(): HasMany
    {
        return $this->hasMany(Candidato::class, 'ID_Vacante', 'ID_Vacante');
    }

    public function entrevistas(): HasMany
    {
        return $this->hasMany(Entrevista::class, 'ID_Vacante', 'ID_Vacante');
    }
}
