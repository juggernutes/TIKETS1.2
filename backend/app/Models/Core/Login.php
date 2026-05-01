<?php
namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Login extends Model
{
    protected $table = 'core.login';
    protected $primaryKey = 'ID_Login';

    const CREATED_AT = 'FechaCreacion';
    const UPDATED_AT = 'FechaModificacion';

    protected $fillable = [
        'Cuenta', 'PasswordHash', 'ID_Usuario', 'Activo',
        'DebeCambiarPassword', 'IntentosFallidos',
        'FechaUltimoIntento', 'UltimoCambioPassword', 'SesionID',
    ];

    protected $hidden = ['PasswordHash'];

    protected $casts = [
        'Activo'               => 'boolean',
        'DebeCambiarPassword'  => 'boolean',
        'IntentosFallidos'     => 'integer',
        'FechaCreacion'        => 'datetime',
        'FechaModificacion'    => 'datetime',
        'FechaUltimoIntento'   => 'datetime',
        'UltimoCambioPassword' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_Usuario', 'ID_Usuario');
    }
}
