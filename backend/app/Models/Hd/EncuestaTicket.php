<?php
namespace App\Models\Hd;

use App\Models\Core\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncuestaTicket extends Model
{
    protected $table = 'hd.encuesta_ticket';
    protected $primaryKey = 'ID_Encuesta';
    public $timestamps = false;

    protected $fillable = ['ID_Ticket', 'Calificacion', 'Comentarios', 'Fecha', 'ID_Usuario'];
    protected $casts = ['Calificacion' => 'integer', 'Fecha' => 'datetime'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ID_Ticket', 'ID_Ticket');
    }
}
