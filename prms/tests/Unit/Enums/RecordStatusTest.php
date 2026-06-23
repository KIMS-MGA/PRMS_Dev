<?php

use App\Enums\RecordStatus;

it('has the correct backing value for each case', function () {
    expect(RecordStatus::Draft->value)->toBe('Draft');
    expect(RecordStatus::Submitted->value)->toBe('Submitted');
    expect(RecordStatus::UnderReview->value)->toBe('Under Review');
    expect(RecordStatus::Returned->value)->toBe('Returned');
    expect(RecordStatus::Completed->value)->toBe('Completed');
});

it('can be created from a string value', function () {
    expect(RecordStatus::from('Draft'))->toBe(RecordStatus::Draft);
    expect(RecordStatus::from('Under Review'))->toBe(RecordStatus::UnderReview);
});

it('tryFrom returns null for unknown status', function () {
    expect(RecordStatus::tryFrom('Unknown'))->toBeNull();
});
