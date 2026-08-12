<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\IntegrationClient;
use App\Models\IntegrationDelivery;
use App\Models\IntegrationEndpoint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;

class IntegrationSampleSeeder extends Seeder
{
    public function run(): void
    {
        // Demo/test-only: client integrasi palsu (pas-papan.hrconnect.local)
        // dengan secret hardcoded (secret-key-123 / DKMS-TEST-001) — jangan
        // pernah di-seed di production. (Guard kedua: DatabaseSeeder juga skip.)
        if (app()->isProduction()) {
            return;
        }

        $company = Company::firstOrFail();

        $client = IntegrationClient::firstOrCreate(
            [
                'code' => 'ERP-TALENT',
                'company_id' => $company->id,
            ],
            [
                'name' => 'Talent Management ERP (PasPapan-legacy)',
                'contact_name' => 'Admin PasPapan',
                'contact_email' => 'admin@pas-papan.local',
                'api_key_hash' => 'sha256='.hash('sha256', 'DKMS-TEST-001'),
                'secret_encrypted' => Crypt::encryptString('secret-key-123'),
                'abilities' => ['integration:attendance.read', 'integration:attendance.write'],
                'allowed_sources' => ['erp', 'mobile'],
                'allowed_ips' => ['127.0.0.1'],
            ]
        );

        $endpoint = IntegrationEndpoint::create([
            'client_id' => $client->id,
            'name' => 'Attendance Events',
            'event_keys' => ['attendance.created'],
            'url' => 'https://pas-papan.hrconnect.local/api/attendance/events',
            'secret' => 'example-secret-key',
            'active' => true,
        ]);

        $delivery = IntegrationDelivery::create([
            'integration_endpoint_id' => $endpoint->id,
            'event_key' => 'attendance.created',
            'payload' => ['employee_number' => 'EMP001', 'clock_in' => '08:00:00'],
            'status' => IntegrationDelivery::STATUS_DELIVERED,
            'attempts' => 1,
            'response_status' => 200,
            'response_body' => '{"status":"ok"}',
            'signature' => hash_hmac('sha256', 'attendance.created', 'example-secret-key'),
            'dispatched_at' => now(),
        ]);

        $this->command->info('✅ Sample integration data created.');
    }
}
