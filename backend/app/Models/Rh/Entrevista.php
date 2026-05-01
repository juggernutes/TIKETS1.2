<?php
namespace App\Models\Rh;

use App\Models\Core\Usuario;
use App\Enums\RhResultadoEntrevista;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entrevista extends Model
{
    protected $table = 'rh.entrevista';
    protected $primaryKey = 'ID_Entrevista';

    const CREATED_AT = 'FechaCreacion';
    const UPDATED_AT = 'FechaModificacion';

    protected $fillable = [
        'ID_Candidato', 'ID_Vacante', 'ID_UsuarioEntrevistador',
        'TipoEntrevista', 'FechaEntrevista', 'DuracionMinutos',
        'Ubicacion', 'Medio', 'Resultado',
        'Calificacion', 'Comentarios', 'Activo',
    ];

    protected $casts = [
        'Activo'           => 'boolean',
        'FechaEntrevista'  => 'datetime',
        'Calificacion'     => 'decimal:2',
        'DuracionMinutos'  => 'integer',
        'FechaCreacion'    => 'datetime',
        'FechaModificacion'=> 'datetime',
        'Resultado'        => RhResultadoEntrevista::class,
    ];

    public function candidato(): BelongsTo
    {
        return $this->belongsTo(Candidato::class, 'ID_Candidato', 'ID_Candidato');
    }

    public function vacante(): BelongsTo
    {
        return $this->belongsTo(Vacante::class, 'ID_Vacante', 'ID_Vacante');
    }

    public function entrevistador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_UsuarioEntrevistador', 'ID_Usuario');
    }

    public function evaluadores(): HasMany
    {
        return $this->hasMany(EntrevistaEvaluador::class, 'ID_Entrevista', 'ID_Entrevista');
    }
}
