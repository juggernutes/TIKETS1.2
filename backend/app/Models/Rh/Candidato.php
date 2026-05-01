<?php
namespace App\Models\Rh;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidato extends Model
{
    protected $table = 'rh.candidato';
    protected $primaryKey = 'ID_Candidato';

    const CREATED_AT = 'FechaCreacion';
    const UPDATED_AT = 'FechaModificacion';

    protected $fillable = [
        'ID_Vacante', 'ID_EstatusCandidato',
        'Nombre', 'ApellidoPaterno', 'ApellidoMaterno',
        'Correo', 'Telefono', 'TelefonoAlterno',
        'FechaNacimiento', 'Genero', 'RFC', 'CURP',
        'Escolaridad', 'Profesion', 'ExperienciaResumen',
        'CV_URL', 'LinkedIn_URL', 'Fuente',
        'PretensionSalarial', 'FechaPostulacion',
        'Observaciones', 'Activo',
    ];

    protected $casts = [
        'Activo'              => 'boolean',
        'FechaNacimiento'     => 'date',
        'FechaPostulacion'    => 'datetime',
        'PretensionSalarial'  => 'decimal:2',
        'FechaCreacion'       => 'datetime',
        'FechaModificacion'   => 'datetime',
    ];

    public function vacante(): BelongsTo
    {
        return $this->belongsTo(Vacante::class, 'ID_Vacante', 'ID_Vacante');
    }

    public function estatus(): BelongsTo
    {
        return $this->belongsTo(EstatusCandidato::class, 'ID_EstatusCandidato', 'ID_EstatusCandidato');
    }

    public function entrevistas(): HasMany
    {
        return $this->hasMany(Entrevista::class, 'ID_Candidato', 'ID_Candidato');
    }

    public function ofertas(): HasMany
    {
        return $this->hasMany(OfertaLaboral::class, 'ID_Candidato', 'ID_Candidato');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->Nombre} {$this->ApellidoPaterno} {$this->ApellidoMaterno}");
    }
}
