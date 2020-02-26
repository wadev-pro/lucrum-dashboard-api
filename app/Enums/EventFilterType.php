<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class EventFilterType extends Enum
{
    const Clicker = 1;
    const ClickNotConverter = 2;
    const Converter = 3;
}
