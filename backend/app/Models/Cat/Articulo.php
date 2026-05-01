<?php
namespace App\Models\Cat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Articulo extends Model
{
    protected $table = 'cat.articulo';
    protected $primaryKey = 'IdArticulo';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'Nombre', 'NombreCorto', 'IdTipoArticulo', 'Peso',
        'Bloqueado', 'IdGrupoArticulo', 'Activo',
    ];

    protected $casts = [
        'Activo'    => 'boolean',
        'Bloqueado' => 'boolean',
        'Peso'      => 'decimal:3',
    ];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(GrupoArticulo::class, 'IdGrupoArticulo', 'IdGrupoArticulo');
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoArticulo::class, 'IdTipoArticulo', 'IdTipoArticulo');
    }
}
