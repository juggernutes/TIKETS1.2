<?php
namespace App\Models\Com;

use App\Models\Core\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaMesPortada extends Model
{
    protected $table = 'com.meta_mes_portada';
    protected $primaryKey = 'ID_MetaMes';
    public $timestamps = false;

    protected $fillable = [
        'Anio', 'Mes', 'Nombre', 'DiasHabiles',
        'ID_UsuarioCreo', 'ID_UsuarioModifico',
    ];

    protected $casts = [
        'Anio'              => 'integer',
        'Mes'               => 'integer',
        'DiasHabiles'       => 'integer',
        'FechaCreacion'     => 'datetime',
        'FechaModificacion' => 'datetime',
    ];

    public function usuarioCreo(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_UsuarioCreo', 'ID_Usuario');
    }

    public function contenido(): HasMany
    {
        return $this->hasMany(MetaMensualContenido::class, 'ID_MetaMes', 'ID_MetaMes');
    }

    public function semanas(): HasMany
    {
        return $this->hasMany(Semana::class, 'ID_MetaMesInicio', 'ID_MetaMes');
    }
}
