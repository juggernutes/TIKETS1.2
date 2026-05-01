<?php

namespace App\Models;

use App\Models\Core\Login;
use App\Models\Core\Rol;
use App\Models\Core\Area;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo User compatible con Laravel Auth/Sanctum.
 * Apunta a core.usuario y usa core.login para la contraseña.
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table      = 'core.usuario';
    protected $primaryKey = 'ID_Usuario';

    const CREATED_AT = 'FechaCreacion';
    const UPDATED_AT = 'FechaModificacion';

    protected $fillable = ['Nombre', 'Email', 'FotoURL', 'ID_Rol', 'ID_Area', 'Activo'];

    protected $hidden = ['remember_token'];

    protected $casts = [
        'Activo'            => 'boolean',
        'FechaCreacion'     => 'datetime',
        'FechaModificacion' => 'datetime',
    ];

    public function getAuthIdentifierName(): string
    {
        return 'ID_Usuario';
    }

    public function getAuthPassword(): string
    {
        return (string) ($this->login?->PasswordHash ?? '');
    }

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
}
