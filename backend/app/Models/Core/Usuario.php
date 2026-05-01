<?php
namespace App\Models\Core;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'core.usuario';
    protected $primaryKey = 'ID_Usuario';

    const CREATED_AT = 'FechaCreacion';
    const UPDATED_AT = 'FechaModificacion';

    protected $fillable = ['Nombre', 'Email', 'FotoURL', 'ID_Rol', 'ID_Area', 'Activo'];

    protected $casts = [
        'Activo'            => 'boolean',
        'FechaCreacion'     => 'datetime',
        'FechaModificacion' => 'datetime',
    ];

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'ID_Rol', 'ID_Rol');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'ID_Area', 'ID_Area');
    }

    public function login(): HasOne
    {
        return $this->hasOne(Login::class, 'ID_Usuario', 'ID_Usuario');
    }

    public function relacion(): HasOne
    {
        return $this->hasOne(UsuarioRelacion::class, 'ID_Usuario', 'ID_Usuario');
    }
}
