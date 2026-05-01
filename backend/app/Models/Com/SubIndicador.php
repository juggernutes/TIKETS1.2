<?php
namespace App\Models\Com;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubIndicador extends Model
{
    protected $table = 'com.sub_indicador';
    protected $primaryKey = 'ID_SubIndicador';
    public $timestamps = false;

    protected $fillable = ['ID_Indicador', 'Clave', 'Nombre', 'Orden', 'Activo'];
    protected $casts    = ['Activo' => 'boolean'];

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class, 'ID_Indicador', 'ID_Indicador');
    }

    public function reglas(): HasMany
    {
        return $this->hasMany(ReglaComision::class, 'ID_SubIndicador', 'ID_SubIndicador');
    }
}
