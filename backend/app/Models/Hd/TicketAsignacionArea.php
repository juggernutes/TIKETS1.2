<?php
namespace App\Models\Hd;

use App\Models\Core\Area;
use App\Models\Core\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAsignacionArea extends Model
{
    protected $table = 'hd.ticket_asignacion_area';
    protected $primaryKey = 'ID_Asignacion';
    public $timestamps = false;

    protected $fillable = ['ID_Ticket', 'ID_Area', 'FechaAsignacion', 'ID_UsuarioAsigno', 'Activa'];
    protected $casts = ['Activa' => 'boolean', 'FechaAsignacion' => 'datetime'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ID_Ticket', 'ID_Ticket');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'ID_Area', 'ID_Area');
    }

    public function usuarioAsigno(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_UsuarioAsigno', 'ID_Usuario');
    }
}
