<?php
namespace App\Models\Com;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semana extends Model
{
    protected $table = 'com.semana';
    protected $primaryKey = 'ID_Semana';
    public $timestamps = false;

    protected $fillable = [
        'Anio', 'Semana', 'FechaInicio', 'FechaFin',
        'ID_MetaMesInicio', 'ID_MetaMesFinal',
        'DiasMesInicio', 'DiasMesFinal', 'Activo', 'ID_UsuarioCreo',
    ];

    protected $casts = [
        'FechaInicio'   => 'date',
        'FechaFin'      => 'date',
        'Anio'          => 'integer',
        'Semana'        => 'integer',
        'DiasMesInicio' => 'integer',
        'DiasMesFinal'  => 'integer',
        'Activo'        => 'boolean',
    ];

    public function metaMesInicio(): BelongsTo
    {
        return $this->belongsTo(MetaMesPortada::class, 'ID_MetaMesInicio', 'ID_MetaMes');
    }

    public function metaMesFinal(): BelongsTo
    {
        return $this->belongsTo(MetaMesPortada::class, 'ID_MetaMesFinal', 'ID_MetaMes');
    }

    public function metasSemanal(): HasMany
    {
        return $this->hasMany(MetaSemanal::class, 'ID_Semana', 'ID_Semana');
    }

    public function corridas(): HasMany
    {
        return $this->hasMany(CorridaComision::class, 'ID_Semana', 'ID_Semana');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(\App\Models\Ped\Pedido::class, 'ID_Semana', 'ID_Semana');
    }
}
