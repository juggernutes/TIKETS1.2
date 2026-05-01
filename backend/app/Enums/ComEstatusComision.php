<?php

namespace App\Enums;

enum ComEstatusComision: string
{
    case CALCULADO  = 'CALCULADO';   // Motor ejecutado, pendiente revisión
    case APROBADO   = 'APROBADO';    // Aprobado por el gerente de sucursal
    case RECHAZADO  = 'RECHAZADO';   // Rechazado, requiere corrección y recálculo
    case PAGADO     = 'PAGADO';      // Comisión liquidada al vendedor
    case CANCELADO  = 'CANCELADO';   // Anulado

    public function label(): string
    {
        return match($this) {
            self::CALCULADO  => 'Calculado',
            self::APROBADO   => 'Aprobado',
            self::RECHAZADO  => 'Rechazado',
            self::PAGADO     => 'Pagado',
            self::CANCELADO  => 'Cancelado',
        };
    }

    /** @return self[] */
    public function transicionesPermitidas(): array
    {
        return match($this) {
            self::CALCULADO  => [self::APROBADO, self::RECHAZADO, self::CANCELADO],
            self::RECHAZADO  => [self::CALCULADO, self::CANCELADO],  // permite recalcular
            self::APROBADO   => [self::PAGADO, self::CANCELADO],
            self::PAGADO     => [],
            self::CANCELADO  => [],
        };
    }

    public function puedeTransicionarA(self $destino): bool
    {
        return in_array($destino, $this->transicionesPermitidas(), true);
    }

    public function esFinal(): bool
    {
        return in_array($this, [self::PAGADO, self::CANCELADO], true);
    }
}
