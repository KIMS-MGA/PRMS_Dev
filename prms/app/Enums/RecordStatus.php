<?php

namespace App\Enums;

enum RecordStatus: string
{
    case Draft       = 'Draft';
    case Submitted   = 'Submitted';
    case UnderReview = 'Under Review';
    case Returned    = 'Returned';
    case Completed   = 'Completed';
}
