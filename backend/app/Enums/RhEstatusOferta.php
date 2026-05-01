<?php
namespace App\Enums;

enum RhEstatusOferta: string
{
    case ENVIADA     = 'ENVIADA';
    case ACEPTADA    = 'ACEPTADA';
    case RECHAZADA   = 'RECHAZADA';
    case VENCIDA     = 'VENCIDA';
    case NEGOCIACION = 'NEGOCIACION';
}
