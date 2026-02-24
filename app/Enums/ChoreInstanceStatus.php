<?php

namespace App\Enums;

enum ChoreInstanceStatus: string
{
    case Upcoming = 'upcoming';
    case Due = 'due';
    case Overdue = 'overdue';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
}
