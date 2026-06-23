<?php

namespace App\Enums;

enum ApprovalAction: string
{
    case Created      = 'created';
    case Updated      = 'updated';
    case Submitted    = 'submitted';
    case Approved     = 'approved';
    case Returned     = 'returned';
    case Forwarded    = 'forwarded';
    case Reviewed     = 'reviewed';
    case AutoAdvanced = 'auto_advanced';
    case AutoApproved = 'auto_approved';
}
