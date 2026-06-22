<?php

use App\Enums\ApprovalAction;

it('has the correct backing value for each case', function () {
    expect(ApprovalAction::Created->value)->toBe('created');
    expect(ApprovalAction::Updated->value)->toBe('updated');
    expect(ApprovalAction::Submitted->value)->toBe('submitted');
    expect(ApprovalAction::Approved->value)->toBe('approved');
    expect(ApprovalAction::Returned->value)->toBe('returned');
    expect(ApprovalAction::Forwarded->value)->toBe('forwarded');
    expect(ApprovalAction::AutoAdvanced->value)->toBe('auto_advanced');
    expect(ApprovalAction::AutoApproved->value)->toBe('auto_approved');
});

it('can be created from a string value', function () {
    expect(ApprovalAction::from('submitted'))->toBe(ApprovalAction::Submitted);
    expect(ApprovalAction::from('auto_advanced'))->toBe(ApprovalAction::AutoAdvanced);
});

it('tryFrom returns null for unknown value', function () {
    expect(ApprovalAction::tryFrom('unknown'))->toBeNull();
});
