<?php
namespace App\Models\Com;

use App\Models\Core\Empleado;
use App\Models\Core\Usuario;
use App\Models\Ped\UnidadOperacional;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComisionBase extends Model
{
    protected $table = 'com.comision_base';
    protected $primaryKey = 'ID_Comision';
    public $timestamps = false;

    protected $fillable = [
        'ID_Semana', 'IdUnidad', 'Numero_Empleado',
        'Estatus', 'ArchivoOrigen', 'Observaciones',
        'ID_UsuarioCreo', 'ID_UsuarioModifico',
    ];

    protected $casts = [
        'FechaCreacion'     => 'datetime',
        'FechaModificacion' => 'datetime',
    ];

    public function semana(): BelongsTo
    {
        return $this->belongsTo(Semana::class, 'ID_Semana', 'ID_Semana');
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(UnidadOperacional::class, 'IdUnidad', 'IdUnidad');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'Numero_Empleado', 'Numero_Empleado');
    }

    public function usuarioCreo(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_UsuarioCreo', 'ID_Usuario');
    }
}
