<?php

use App\Models\Attendance;
use App\Models\Setting;
use App\Models\User;
use App\Support\ApiTokenPermission;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$email = getenv('E2E_EMAIL') ?: 'apk.e2e.user@paspapan.test';
$password = getenv('E2E_PASSWORD') ?: '12345678';
$latitude = (float) (getenv('SMOKE_LATITUDE') ?: '-6.200000');
$longitude = (float) (getenv('SMOKE_LONGITUDE') ?: '106.816666');

$userPayload = [
    'nip' => 'APK-E2E-ATT',
    'name' => 'APK E2E Attendance User',
    'phone' => '081234567898',
    'gender' => 'male',
    'address' => 'APK E2E Attendance Address',
    'password' => Hash::make($password),
    'group' => 'user',
    'email_verified_at' => now(),
];

foreach ([
    'city' => 'Jakarta',
    'employment_status' => 'active',
] as $column => $value) {
    if (Schema::hasColumn('users', $column)) {
        $userPayload[$column] = $value;
    }
}

$user = User::query()->updateOrCreate(['email' => $email], $userPayload);
$user->forceFill(['email_verified_at' => now()])->save();

foreach ([
    'attendance.require_face_enrollment',
    'attendance.require_face_verification',
] as $key) {
    Setting::query()->updateOrCreate(
        ['key' => $key],
        ['value' => '0', 'group' => 'attendance', 'type' => 'boolean']
    );
    Setting::flushCache($key);
}

if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
    DB::table('sessions')->where('user_id', $user->id)->delete();
}

$user->tokens()
    ->where('name', 'apk-attendance-e2e')
    ->delete();

Attendance::query()
    ->whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
    ->whereDate('date', now()->toDateString())
    ->delete();

$barcodeValue = 'APK-E2E-'.Str::upper(Str::random(12));

$token = $user->createToken('apk-attendance-e2e', [
    ApiTokenPermission::DEVICE_PHOTO,
    ApiTokenPermission::DEVICE_LOCATION,
    ApiTokenPermission::DEVICE_PERMISSIONS,
])->plainTextToken;

echo json_encode([
    'email' => $email,
    'password' => $password,
    'api_token' => $token,
    'barcode_data' => $barcodeValue,
    'latitude' => $latitude,
    'longitude' => $longitude,
], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES).PHP_EOL;
