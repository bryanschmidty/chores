<?php

namespace App\Enums;

enum RecurrenceType: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case EveryNDays = 'every_n_days';
    case Weekdays = 'weekdays';
}
