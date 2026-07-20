<?php

namespace App\Enums;

enum ChatDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
}
