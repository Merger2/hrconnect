<?php

use App\Models\FaceDescriptor;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$state = $argv[1] ?? null;
$userEmail = getenv('APK_SCREENSHOT_USER_EMAIL') ?: 'apk.demo.user@paspapan.test';
$user = User::query()->where('email', $userEmail)->firstOrFail();

match ($state) {
    'face-unregistered' => FaceDescriptor::query()
        ->where('user_id', $user->id)
        ->delete(),
    'face-registered' => FaceDescriptor::query()->updateOrCreate(
        ['user_id' => $user->id],
        ['descriptor' => array_fill(0, 128, 0.01)],
    ),
    default => throw new InvalidArgumentException("Unsupported screenshot state [{$state}]."),
};

echo json_encode([
    'ok' => true,
    'state' => $state,
    'user_email' => $user->email,
], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES).PHP_EOL;
