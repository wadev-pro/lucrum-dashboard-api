<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class EventType extends Enum
{
    const SentMtSms = 1;
    const ClickReceived = 2;
    const ConversionReceived = 3;
    const MoSmsReceived = 4;
}
