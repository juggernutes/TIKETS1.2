<?php
namespace App\Enums;

enum RhEstatusVacante: string
{
    case ABIERTA   = 'ABIERTA';
    case PAUSADA   = 'PAUSADA';
    case CERRADA   = 'CERRADA';
    case CANCELADA = 'CANCELADA';
}
