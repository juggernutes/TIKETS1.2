<?php
namespace App\Models\Rh;

use App\Enums\RhEstatusOferta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfertaLaboral extends Model
{
    protected $table = 'rh.oferta_laboral';
    protected $primaryKey = 'ID_Oferta';

    const CREATED_AT = 'FechaCreacion';
    const UPDATED_AT = 'FechaModificacion';

    protected $fillable = [
        'ID_Candidato', 'ID_Vacante',
        'SalarioOfertado', 'FechaOferta', 'FechaVencimiento',
        'FechaRespuesta', 'Estatus', 'MotivoRechazo',
        'Contrapropuesta', 'FechaIngreso', 'Activo',
    ];

    protected $casts = [
        'Activo'           => 'boolean',
        'SalarioOfertado'  => 'decimal:2',
        'Contrapropuesta'  => 'decimal:2',
        'FechaOferta'      => 'datetime',
        'FechaVencimiento' => 'datetime',
        'FechaRespuesta'   => 'datetime',
        'FechaIngreso'     => 'date',
        'FechaCreacion'    => 'datetime',
        'FechaModificacion'=> 'datetime',
        'Estatus'          => RhEstatusOferta::class,
    ];

    public function candidato(): BelongsTo
    {
        return $this->belongsTo(Candidato::class, 'ID_Candidato', 'ID_Candidato');
    }

    public function vacante(): BelongsTo
    {
        return $this->belongsTo(Vacante::class, 'ID_Vacante', 'ID_Vacante');
    }
}
