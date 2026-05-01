<?php
namespace App\Models\Hd;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sla extends Model
{
    protected $table = 'hd.sla';
    protected $primaryKey = 'ID_SLA';
    public $timestamps = false;

    protected $fillable = ['Nombre', 'ID_Area', 'Prioridad', 'HorasRespuesta', 'HorasResolucion', 'Activo'];
    protected $casts = ['Activo' => 'boolean'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Core\Area::class, 'ID_Area', 'ID_Area');
    }
}
