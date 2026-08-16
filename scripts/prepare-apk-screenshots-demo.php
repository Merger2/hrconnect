<?php

use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeDocumentType;
use App\Models\FaceDescriptor;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$password = getenv('APK_SCREENSHOT_PASSWORD') ?: '12345678';
$userEmail = getenv('APK_SCREENSHOT_USER_EMAIL') ?: 'apk.demo.user@hrconnect.test';
$adminEmail = getenv('APK_SCREENSHOT_ADMIN_EMAIL') ?: 'apk.demo.superadmin@hrconnect.test';

// Mock-miss fix (2026-08-16): fitur lisensi enterprise (validasi palsu
// strlen >= 32) dihapus total — licensing = non-goal PRD. Fungsi
// screenshotEnterpriseLicense()/screenshotEnterprisePrivateKey() ikut dihapus.

foreach ([
    'app.company_name' => ['value' => 'PT. PasPapan Indonesia', 'group' => 'identity', 'type' => 'text'],
    'app.support_contact' => ['value' => 'https://t.me/RiprLutuk', 'group' => 'identity', 'type' => 'text'],
] as $key => $payload) {
    Setting::query()->updateOrCreate(['key' => $key], $payload);
    Setting::flushCache($key);
}

$basePayload = [
    'phone' => '081234567899',
    'gender' => 'male',
    'address' => 'APK Demo Address',
    'password' => Hash::make($password),
    'email_verified_at' => now(),
];

foreach ([
    'city' => 'Jakarta',
    'employment_status' => 'active',
] as $column => $value) {
    if (Schema::hasColumn('users', $column)) {
        $basePayload[$column] = $value;
    }
}

$user = User::query()->updateOrCreate(
    ['email' => $userEmail],
    $basePayload + [
        'nip' => 'APK-DEMO-USER',
        'name' => 'APK Demo User',
        'group' => 'user',
    ],
);

$admin = User::query()->updateOrCreate(
    ['email' => $adminEmail],
    $basePayload + [
        'nip' => 'APK-DEMO-ADMIN',
        'name' => 'APK Demo Superadmin',
        'group' => 'superadmin',
    ],
);

$subordinate = User::query()->updateOrCreate(
    ['email' => 'apk.demo.subordinate@hrconnect.test'],
    $basePayload + [
        'nip' => 'APK-DEMO-SUB',
        'name' => 'APK Demo Subordinate',
        'group' => 'user',
        'manager_id' => $user->id,
    ],
);

foreach ([$user, $admin] as $account) {
    $account->forceFill([
        'password' => Hash::make($password),
        'email_verified_at' => now(),
    ])->save();
}

if (Schema::hasTable('face_descriptors')) {
    if ($user->employee) {
        FaceDescriptor::query()->updateOrCreate(
            ['employee_id' => $user->employee->id],
            ['embedding' => array_fill(0, 128, 0.01), 'is_active' => true],
        );
    }
}

$subordinate->forceFill([
    'manager_id' => $user->id,
    'password' => Hash::make($password),
    'email_verified_at' => now(),
])->save();

if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
    DB::table('sessions')
        ->whereIn('user_id', [$user->id, $admin->id, $subordinate->id])
        ->delete();
}

$type = EmployeeDocumentType::query()->updateOrCreate(
    ['code' => 'npwp'],
    [
        'name' => 'NPWP',
        'category' => 'finance',
        'is_active' => true,
        'employee_requestable' => false,
        'admin_requestable' => true,
        'requires_employee_upload' => true,
        'auto_generate_enabled' => false,
    ],
);

$request = EmployeeDocumentRequest::query()
    ->where('user_id', $user->id)
    ->where('purpose', 'APK screenshot demo')
    ->latest()
    ->first();

if (! $request) {
    $request = EmployeeDocumentRequest::query()->create([
        'user_id' => $user->id,
        'document_type_id' => $type->id,
        'document_type' => $type->code,
        'request_source' => EmployeeDocumentRequest::SOURCE_ADMIN,
        'purpose' => 'APK screenshot demo',
        'details' => 'Demo document request for APK page screenshots.',
        'due_date' => now()->addWeek()->toDateString(),
        'status' => EmployeeDocumentRequest::STATUS_REQUESTED,
        'metadata' => ['screenshot_demo' => (string) Str::uuid()],
    ]);
}

echo json_encode([
    'user_email' => $user->email,
    'admin_email' => $admin->email,
    'password' => $password,
    'document_request_id' => $request->id,
], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES).PHP_EOL;
