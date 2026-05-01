<?php
namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsuarioRelacion extends Model
{
    protected $table = 'core.usuario_relacion';
    protected $primaryKey = 'ID_UsuarioRelacion';
    public $timestamps = false;

    protected $fillable = ['ID_Usuario', 'Numero_Empleado', 'ID_Proveedor', 'Activo'];
    protected $casts = ['Activo' => 'boolean'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_Usuario', 'ID_Usuario');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'Numero_Empleado', 'Numero_Empleado');
    }
}
