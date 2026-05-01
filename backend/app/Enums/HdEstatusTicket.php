<?php
namespace App\Enums;

enum HdEstatusTicket: string
{
    case NUEVO       = 'NUEVO';
    case ASIGNADO    = 'ASIGNADO';
    case EN_PROCESO  = 'EN_PROCESO';
    case EN_ESPERA   = 'EN_ESPERA';
    case RESUELTO    = 'RESUELTO';
    case CERRADO     = 'CERRADO';
    case CANCELADO   = 'CANCELADO';
}
