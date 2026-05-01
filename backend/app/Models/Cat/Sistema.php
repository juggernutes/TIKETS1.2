<?php
namespace App\Models\Cat;

use App\Models\Core\Area;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sistema extends Model
{
    protected $table = 'cat.sistema';
    protected $primaryKey = 'ID_Sistema';
    public $timestamps = false;

    protected $fillable = ['ID_Area', 'Nombre', 'Descripcion', 'Activo'];
    protected $casts = ['Activo' => 'boolean'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'ID_Area', 'ID_Area');
    }
}
