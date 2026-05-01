<?php
namespace App\Enums;

enum RhResultadoEntrevista: string
{
    case PENDIENTE   = 'PENDIENTE';
    case APROBADO    = 'APROBADO';
    case RECHAZADO   = 'RECHAZADO';
    case REPROGRAMAR = 'REPROGRAMAR';
    case NO_ASISTIO  = 'NO_ASISTIO';
}
