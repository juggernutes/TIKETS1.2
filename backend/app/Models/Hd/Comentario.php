<?php
namespace App\Models\Hd;

use App\Models\Core\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comentario extends Model
{
    protected $table = 'hd.comentario';
    protected $primaryKey = 'ID_Comentario';
    public $timestamps = false;

    protected $fillable = ['ID_Ticket', 'ID_Usuario', 'Mensaje', 'EsInterno', 'Fecha'];
    protected $casts = ['EsInterno' => 'boolean', 'Fecha' => 'datetime'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ID_Ticket', 'ID_Ticket');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_Usuario', 'ID_Usuario');
    }
}
