<?php
namespace App\Models\Com;

use App\Models\Core\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultadoIndicador extends Model
{
    protected $table = 'com.resultado_indicador';
    protected $primaryKey = 'ID_Resultado';
    public $timestamps = false;

    protected $fillable = [
        'ID_Base', 'ID_Indicador', 'ID_SubIndicador',
        'ValorReal', 'Meta',
        'ClientesActivos', 'ClientesVisitados', 'ClientesConCompra',
        'PorcentajeCumplimiento', 'MontoCalculado',
        'Observaciones', 'ID_UsuarioCreo',
    ];

    protected $casts = [
        'ValorReal'              => 'decimal:6',
        'Meta'                   => 'decimal:6',
        'PorcentajeCumplimiento' => 'decimal:6',
        'MontoCalculado'         => 'decimal:2',
        'FechaCreacion'          => 'datetime',
    ];

    public function base(): BelongsTo
    {
        return $this->belongsTo(BaseComisionSemanal::class, 'ID_Base', 'ID_Base');
    }

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class, 'ID_Indicador', 'ID_Indicador');
    }

    public function subIndicador(): BelongsTo
    {
        return $this->belongsTo(SubIndicador::class, 'ID_SubIndicador', 'ID_SubIndicador');
    }
}
