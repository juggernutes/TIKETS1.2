<?php
namespace App\Models\Com;

use App\Models\Core\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultadoDoc extends Model
{
    protected $table = 'com.resultado_doc';
    protected $primaryKey = 'ID_ResultadoDoc';
    public $timestamps = false;

    protected $fillable = [
        'ID_Base', 'ID_SubIndicador',
        'Cumplido', 'MontoConcepto', 'AlcancePesos',
        'Observaciones', 'ID_UsuarioCreo',
    ];

    protected $casts = [
        'Cumplido'      => 'boolean',
        'MontoConcepto' => 'decimal:2',
        'AlcancePesos'  => 'decimal:2',
        'FechaCreacion' => 'datetime',
    ];

    public function base(): BelongsTo
    {
        return $this->belongsTo(BaseComisionSemanal::class, 'ID_Base', 'ID_Base');
    }

    public function subIndicador(): BelongsTo
    {
        return $this->belongsTo(SubIndicador::class, 'ID_SubIndicador', 'ID_SubIndicador');
    }
}
