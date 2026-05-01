<?php
namespace App\Models\Com;

use App\Models\Core\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CorridaComision extends Model
{
    protected $table = 'com.corrida_comision';
    protected $primaryKey = 'ID_Corrida';
    public $timestamps = false;

    protected $fillable = [
        'ID_Semana', 'FechaInicio', 'FechaFin', 'Estatus',
        'ArchivoOrigen', 'Observaciones',
        'ID_UsuarioCreo', 'ID_UsuarioModifico',
    ];

    protected $casts = [
        'FechaInicio'       => 'date',
        'FechaFin'          => 'date',
        'FechaCreacion'     => 'datetime',
        'FechaModificacion' => 'datetime',
    ];

    public function semana(): BelongsTo
    {
        return $this->belongsTo(Semana::class, 'ID_Semana', 'ID_Semana');
    }

    public function usuarioCreo(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_UsuarioCreo', 'ID_Usuario');
    }

    public function bases(): HasMany
    {
        return $this->hasMany(BaseComisionSemanal::class, 'ID_Corrida', 'ID_Corrida');
    }

    public function log(): HasMany
    {
        return $this->hasMany(CorridaComisionLog::class, 'ID_Corrida', 'ID_Corrida');
    }
}
