<?php
namespace App\Models\Com;

use App\Models\Core\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorridaComisionLog extends Model
{
    protected $table = 'com.corrida_comision_log';
    protected $primaryKey = 'ID_Log';
    public $timestamps = false;

    protected $fillable = [
        'ID_Corrida', 'EstadoAnterior', 'EstadoNuevo',
        'Comentario', 'ID_Usuario', 'IP',
    ];

    protected $casts = ['Fecha' => 'datetime'];

    public function corrida(): BelongsTo
    {
        return $this->belongsTo(CorridaComision::class, 'ID_Corrida', 'ID_Corrida');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_Usuario', 'ID_Usuario');
    }
}
