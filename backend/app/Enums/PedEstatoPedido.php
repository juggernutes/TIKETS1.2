<?php
namespace App\Enums;

enum PedEstatoPedido: string
{
    case PENDIENTE   = 'PENDIENTE';
    case AUTORIZADO  = 'AUTORIZADO';
    case SURTIDO     = 'SURTIDO';
    case CANCELADO   = 'CANCELADO';
}
