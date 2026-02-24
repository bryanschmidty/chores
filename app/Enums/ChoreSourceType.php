<?php

namespace App\Enums;

enum ChoreSourceType: string
{
    case Recurring = 'recurring';
    case AdHoc = 'ad_hoc';
}
