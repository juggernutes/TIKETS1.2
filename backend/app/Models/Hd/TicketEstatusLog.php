<?php
namespace App\Models\Hd;

use App\Models\Core\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEstatusLog extends Model
{
    protected $table = 'hd.ticket_estatus_log';
    protected $primaryKey = 'ID_Log';
    public $timestamps = false;

    protected $fillable = [
        'ID_Ticket', 'ID_Estatus_Anterior', 'ID_Estatus_Nuevo',
        'ID_Usuario', 'Comentario', 'Fecha',
    ];

    protected $casts = ['Fecha' => 'datetime'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_Usuario', 'ID_Usuario');
    }
}
