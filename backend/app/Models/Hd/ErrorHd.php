<?php
namespace App\Models\Hd;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErrorHd extends Model
{
    protected $table = 'hd.error';
    protected $primaryKey = 'ID_Error';
    public $timestamps = false;

    protected $fillable = ['Descripcion', 'Tipo', 'Activo'];
    protected $casts = ['Activo' => 'boolean'];

    public function tipoError(): BelongsTo
    {
        return $this->belongsTo(TipoError::class, 'Tipo', 'ID_TipoError');
    }
}
