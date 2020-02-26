<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ReportStatusType extends Enum
{
    const InProgress = 1;
    const Completed = 2;
    const Failed = 3;
}
