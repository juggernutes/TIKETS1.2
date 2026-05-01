<?php
namespace App\Models\Com;

use App\Models\Core\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AjusteComision extends Model
{
    protected $table = 'com.ajuste_comision';
    protected $primaryKey = 'ID_Ajuste';
    public $timestamps = false;

    protected $fillable = [
        'ID_Base', 'TipoAjuste', 'DiasDescuento',
        'Monto', 'Motivo', 'ID_UsuarioCreo',
    ];

    protected $casts = [
        'Monto'         => 'decimal:2',
        'FechaCreacion' => 'datetime',
    ];

    public function base(): BelongsTo
    {
        return $this->belongsTo(BaseComisionSemanal::class, 'ID_Base', 'ID_Base');
    }

    public function usuarioCreo(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_UsuarioCreo', 'ID_Usuario');
    }
}
