<?php

use App\Models\CashAdvance;
use App\Models\User;
use App\Notifications\CashAdvanceStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function cashAdvanceFor(User $user, string $status): CashAdvance
{
    return CashAdvance::create([
        'user_id' => $user->id,
        'amount' => 1500000,
        'purpose' => 'Medical expenses bridge',
        'status' => $status,
    ]);
}

test('rejected cash advance email renders without phantom rejection reason line', function () {
    Mail::fake();

    $user = User::factory()->create();
    $advance = cashAdvanceFor($user, 'rejected');

    $mail = (new CashAdvanceStatusUpdated($advance))->toMail($user);
    $body = implode("\n", $mail->introLines);

    expect($mail->subject)->toBe('Pengajuan Kasbon Ditolak')
        ->and($body)->toContain('Status: Ditolak')
        // cash_advances has no rejection_reason column — the line must never render
        ->and($body)->not->toContain('Alasan:');
});

test('approved cash advance email renders without phantom notes line', function () {
    Mail::fake();

    $user = User::factory()->create();
    $advance = cashAdvanceFor($user, 'approved');

    $mail = (new CashAdvanceStatusUpdated($advance))->toMail($user);
    $body = implode("\n", $mail->introLines);

    expect($mail->subject)->toBe('Pengajuan Kasbon Disetujui Final')
        ->and($body)->toContain('Status: Disetujui Final')
        // cash_advances has no notes column — the line must never render
        ->and($body)->not->toContain('Catatan:');
});

test('stage one approval email uses stage one subject', function () {
    Mail::fake();

    $user = User::factory()->create();
    $advance = cashAdvanceFor($user, 'approved_l1');

    $mail = (new CashAdvanceStatusUpdated($advance))->toMail($user);
    $body = implode("\n", $mail->introLines);

    expect($mail->subject)->toBe('Pengajuan Kasbon Disetujui Tahap 1')
        ->and($body)->toContain('Status: Disetujui (Tahap 1)');
});

test('database payload contains status and amount only', function () {
    Mail::fake();

    $user = User::factory()->create();
    $advance = cashAdvanceFor($user, 'rejected');

    $payload = (new CashAdvanceStatusUpdated($advance))->toArray($user);

    expect($payload)->toHaveKeys(['type', 'advance_id', 'status', 'amount', 'message'])
        ->and($payload['status'])->toBe('rejected');
});
