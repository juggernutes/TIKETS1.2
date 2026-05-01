<?php
namespace App\Models\Rh;

use App\Models\Core\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntrevistaEvaluador extends Model
{
    protected $table = 'rh.entrevista_evaluador';
    protected $primaryKey = 'ID_EvalEntrevista';
    public $timestamps = false;

    protected $fillable = [
        'ID_Entrevista', 'ID_Usuario', 'Rol',
        'Calificacion', 'Comentarios', 'FechaEvaluacion',
    ];

    protected $casts = [
        'Calificacion'    => 'decimal:2',
        'FechaEvaluacion' => 'datetime',
    ];

    public function entrevista(): BelongsTo
    {
        return $this->belongsTo(Entrevista::class, 'ID_Entrevista', 'ID_Entrevista');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_Usuario', 'ID_Usuario');
    }
}
