<?php
namespace App\Models\Hd;

use App\Models\Core\Area;
use App\Models\Core\Empleado;
use App\Models\Core\Proveedor;
use App\Models\Core\Usuario;
use App\Models\Cat\Sistema;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    protected $table = 'hd.ticket';
    protected $primaryKey = 'ID_Ticket';

    const CREATED_AT = 'FechaCreacion';
    const UPDATED_AT = 'FechaModificacion';

    protected $fillable = [
        'SerieFolio', 'Numero_Empleado', 'ID_Area_Origen', 'ID_Area_Responsable',
        'ID_Usuario_Reporta',
        'ID_Sistema', 'ID_Error', 'Descripcion', 'FechaReporte',
        'FechaAsignacion', 'FechaSolucion', 'ID_Solucion', 'DetalleSolucion',
        'ID_Soporte', 'ID_Estatus', 'ID_Usuario_Cierra', 'Activo',
        'Prioridad', 'ID_SLA', 'FechaLimite', 'FueraDeSLA',
        'ID_Proveedor', 'FechaEnvioProveedor', 'SeguimientoProveedor',
    ];

    protected $casts = [
        'Activo'            => 'boolean',
        'FueraDeSLA'        => 'boolean',
        'FechaReporte'      => 'datetime',
        'FechaAsignacion'   => 'datetime',
        'FechaSolucion'     => 'datetime',
        'FechaEnvioProveedor' => 'datetime',
        'FechaLimite'       => 'datetime',
        'FechaCreacion'     => 'datetime',
        'FechaModificacion' => 'datetime',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'Numero_Empleado', 'Numero_Empleado');
    }

    public function areaOrigen(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'ID_Area_Origen', 'ID_Area');
    }

    public function areaResponsable(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'ID_Area_Responsable', 'ID_Area');
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class, 'ID_Sistema', 'ID_Sistema');
    }

    public function error(): BelongsTo
    {
        return $this->belongsTo(ErrorHd::class, 'ID_Error', 'ID_Error');
    }

    public function solucion(): BelongsTo
    {
        return $this->belongsTo(Solucion::class, 'ID_Solucion', 'ID_Solucion');
    }

    public function soporte(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_Soporte', 'ID_Usuario');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'ID_Proveedor', 'ID_Proveedor');
    }

    public function reporta(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ID_Usuario_Reporta', 'ID_Usuario');
    }

    public function estatus(): BelongsTo
    {
        return $this->belongsTo(EstatusHd::class, 'ID_Estatus', 'ID_Estatus');
    }

    public function comentarios(): HasMany
    {
        return $this->hasMany(Comentario::class, 'ID_Ticket', 'ID_Ticket');
    }

    public function encuesta(): HasOne
    {
        return $this->hasOne(EncuestaTicket::class, 'ID_Ticket', 'ID_Ticket');
    }

    public function asignacionesArea(): HasMany
    {
        return $this->hasMany(TicketAsignacionArea::class, 'ID_Ticket', 'ID_Ticket');
    }

    public function estatusLogs(): HasMany
    {
        return $this->hasMany(TicketEstatusLog::class, 'ID_Ticket', 'ID_Ticket');
    }

    public function agenteLogs(): HasMany
    {
        return $this->hasMany(TicketAgenteLog::class, 'ID_Ticket', 'ID_Ticket');
    }

    public function sla(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Hd\Sla::class, 'ID_SLA', 'ID_SLA');
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }
}
