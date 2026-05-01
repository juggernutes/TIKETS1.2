<?php
namespace App\Models\Hd;

use App\Models\Core\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAgenteLog extends Model
{
    protected $table = 'hd.ticket_agente_log';
    protected $primaryKey = 'ID_Log';
    public $timestamps = false;

    protected $fillable = [
        'ID_Ticket', 'ID_Soporte_Anterior', 'ID_Soporte_Nuevo',
        'ID_UsuarioAsigno', 'Fecha',
    ];

    protected $casts = ['Fecha' => 'datetime'];

    public function usuarioAsigno(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_UsuarioAsigno', 'ID_Usuario');
    }
}
