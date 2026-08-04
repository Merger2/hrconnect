<?php

use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Support\NotificationPreferenceService;

test('users can configure notification channels and external routes per event', function () {
    $user = User::factory()->create();
    $service = app(NotificationPreferenceService::class);

    $preference = $service->setPreference(
        $user,
        'reimbursement.requested',
        [
            UserNotificationPreference::CHANNEL_IN_APP,
            UserNotificationPreference::CHANNEL_EMAIL,
        ],
        digestEnabled: true,
        digestFrequency: 'daily',
    );

    expect($preference->channels)->toBe([
        UserNotificationPreference::CHANNEL_IN_APP,
        UserNotificationPreference::CHANNEL_EMAIL,
    ])
        ->and($service->laravelChannelsFor($user, 'reimbursement.requested'))->toBe(['database', 'mail'])
        ->and($service->prefers($user, 'reimbursement.requested', UserNotificationPreference::CHANNEL_EMAIL))->toBeTrue()
        ->and($service->externalRoutesFor($user, 'reimbursement.requested'))->toBeEmpty()
        ->and($service->digestRecipients('reimbursement.requested', 'daily'))->toHaveCount(1);
});

test('notification preferences fall back to in app channel', function () {
    $user = User::factory()->create();
    $service = app(NotificationPreferenceService::class);

    expect($service->channelsFor($user, 'attendance.risk'))->toBe(['database'])
        ->and($service->laravelChannelsFor($user, 'attendance.risk'))->toBe(['database']);
});
