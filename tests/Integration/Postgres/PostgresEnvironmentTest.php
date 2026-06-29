<?php

use App\Enums\PayrollStatus;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\FaceNotRecognizedException;
use App\Exceptions\FaceNotRegisteredException;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\FaceRecognitionService;
use App\Services\PayrollCalculatorService;
use Carbon\CarbonImmutable;
use Database\Seeders\PayrollConfigSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\Vector;

uses(RefreshDatabase::class);

test('PostgreSQL integration suite runs against pgsql', function () {
    expect(DB::getDriverName())->toBe('pgsql');
});

test('required PostgreSQL extensions are installed', function () {
    $extensions = collect(DB::select(<<<'SQL'
        SELECT extname
        FROM pg_extension
        WHERE extname IN ('vector', 'pg_trgm', 'pgcrypto')
        ORDER BY extname
    SQL))->pluck('extname')->all();

    expect($extensions)->toBe(['pg_trgm', 'pgcrypto', 'vector']);
});

function createPostgresEmployee(array $attributes = []): int
{
    $sequence = fake()->unique()->numberBetween(1000, 999999);

    $companyId = DB::table('companies')->insertGetId([
        'name' => $attributes['company_name'] ?? 'PT PostgreSQL Test',
        'code' => $attributes['company_code'] ?? "PG{$sequence}",
        'phone' => '021000000',
        'email' => fake()->unique()->safeEmail(),
        'npwp' => '00.000.000.0-000.000',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $branchId = DB::table('branches')->insertGetId([
        'company_id' => $companyId,
        'name' => 'HQ',
        'address' => 'Jakarta',
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius' => 100,
        'is_main' => true,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $departmentId = DB::table('departments')->insertGetId([
        'branch_id' => $branchId,
        'name' => 'Engineering',
        'code' => "ENG{$sequence}",
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $positionId = DB::table('positions')->insertGetId([
        'department_id' => $departmentId,
        'name' => 'Staff',
        'code' => "STF{$sequence}",
        'grade' => 1,
        'basic_salary' => 5_000_000,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $userId = DB::table('users')->insertGetId([
        'name' => 'Postgres User',
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $faceEmbedding = $attributes['face_embedding'] ?? null;

    $employee = Employee::query()->create([
        'user_id' => $userId,
        'company_id' => $companyId,
        'branch_id' => $branchId,
        'department_id' => $departmentId,
        'position_id' => $positionId,
        'employee_number' => $attributes['employee_number'] ?? fake()->unique()->bothify('PG-###'),
        'full_name' => $attributes['full_name'] ?? 'Postgres Employee',
        'nik' => $attributes['nik'] ?? fake()->unique()->numerify('327601010199####'),
        'phone' => $attributes['phone'] ?? fake()->unique()->numerify('08123456####'),
        'gender' => 'L',
        'marital_status' => 'single',
        'employment_type' => 'permanent',
        'salary_type' => 'monthly',
        'status' => 'active',
        'birth_date' => '1990-01-01',
        'join_date' => '2020-01-01',
        'education_level' => 'bachelor',
        'institution_name' => 'PostgreSQL University',
        'graduation_year' => 2012,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    if ($faceEmbedding !== null) {
        $employee->forceFill(['face_embedding' => $faceEmbedding])->save();
    }

    return $employee->id;
}

function createPostgresLeaveType(): int
{
    return DB::table('leave_types')->insertGetId([
        'name' => 'Cuti Tahunan',
        'code' => fake()->unique()->lexify('ANN???'),
        'quota' => 12,
        'is_paid' => true,
        'deducts_from_quota' => true,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('leave balance constraint allows carry forward usage beyond base quota', function () {
    $employeeId = createPostgresEmployee(['employee_number' => 'PG-001']);
    $leaveTypeId = createPostgresLeaveType();

    DB::table('leave_balances')->insert([
        'employee_id' => $employeeId,
        'leave_type_id' => $leaveTypeId,
        'year' => 2026,
        'quota' => 12,
        'used' => 13,
        'carry_forward' => 3,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('leave_balances')->where('employee_id', $employeeId)->value('used'))
        ->toBe('13.0');
});

test('leave balance constraint rejects usage beyond quota plus carry forward', function () {
    $employeeId = createPostgresEmployee(['employee_number' => 'PG-002']);
    $leaveTypeId = createPostgresLeaveType();

    $this->expectException(QueryException::class);

    DB::table('leave_balances')->insert([
        'employee_id' => $employeeId,
        'leave_type_id' => $leaveTypeId,
        'year' => 2026,
        'quota' => 12,
        'used' => 16,
        'carry_forward' => 3,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

test('pgvector stores 128 dimension face embeddings and supports nearest neighbor queries', function () {
    $baseEmbedding = array_fill(0, 128, 0.1);
    $nearEmbedding = array_fill(0, 128, 0.11);

    $firstEmployeeId = createPostgresEmployee([
        'employee_number' => 'PG-V01',
        'face_embedding' => new Vector($baseEmbedding),
    ]);

    createPostgresEmployee([
        'employee_number' => 'PG-V02',
        'face_embedding' => new Vector(array_fill(0, 128, 0.9)),
    ]);

    $nearest = Employee::query()
        ->nearestNeighbors('face_embedding', new Vector($nearEmbedding), Distance::Cosine)
        ->first();

    expect($nearest)->not->toBeNull()
        ->and($nearest->id)->toBe($firstEmployeeId)
        ->and($nearest->face_embedding)->toBeInstanceOf(Vector::class)
        ->and($nearest->face_embedding->toArray())->toHaveCount(128);
});

test('FaceRecognitionService verifies matching face embeddings and rejects distant embeddings', function () {
    $employeeId = createPostgresEmployee([
        'employee_number' => 'PG-F01',
        'face_embedding' => new Vector(array_fill(0, 128, 0.1)),
    ]);

    $employee = Employee::query()->findOrFail($employeeId);
    $service = app(FaceRecognitionService::class);

    expect($service->verifyFace($employee, array_fill(0, 128, 0.1)))
        ->toMatchArray(['valid' => true]);

    $service->verifyFace($employee, [1.0, ...array_fill(0, 127, 0.0)]);
})->throws(FaceNotRecognizedException::class);

test('FaceRecognitionService respects CompanySetting threshold override on PostgreSQL', function () {
    CompanySetting::set('face_distance_threshold', 0.5);

    $employeeId = createPostgresEmployee([
        'employee_number' => 'PG-F02',
        'face_embedding' => new Vector(array_fill(0, 128, 0.1)),
    ]);

    $employee = Employee::query()->findOrFail($employeeId);
    $service = app(FaceRecognitionService::class);

    // Query with first 35 dims=1, rest=0 → cos_dist ≈ 0.477
    // Fails at default 0.15 threshold, passes at overridden 0.5
    $nearQuery = [...array_fill(0, 35, 1.0), ...array_fill(35, 93, 0.0)];

    expect($service->verifyFace($employee, $nearQuery))
        ->toMatchArray(['valid' => true]);
});

test('FaceRecognitionService throws FaceNotRegisteredException for null embedding on PostgreSQL', function () {
    $employeeId = createPostgresEmployee(['employee_number' => 'PG-F03']);

    $employee = Employee::query()->findOrFail($employeeId);
    $service = app(FaceRecognitionService::class);

    expect(fn () => $service->verifyFace($employee, array_fill(0, 128, 0.1)))
        ->toThrow(FaceNotRegisteredException::class, 'belum terdaftar');
});

test('FaceRecognitionService returns similarity_percentage on successful match', function () {
    $employeeId = createPostgresEmployee([
        'employee_number' => 'PG-F04',
        'face_embedding' => new Vector(array_fill(0, 128, 0.1)),
    ]);

    $employee = Employee::query()->findOrFail($employeeId);
    $result = app(FaceRecognitionService::class)->verifyFace($employee, array_fill(0, 128, 0.1));

    expect($result['valid'])->toBeTrue();
    expect($result['similarity_percentage'])->toBeGreaterThanOrEqual(99.0);
});

test('FaceRecognitionService throws FaceNotRecognizedException with similarity message for distant match', function () {
    $employeeId = createPostgresEmployee([
        'employee_number' => 'PG-F05',
        'face_embedding' => new Vector(array_fill(0, 128, 0.1)),
    ]);

    $employee = Employee::query()->findOrFail($employeeId);
    $service = app(FaceRecognitionService::class);

    expect(fn () => $service->verifyFace($employee, [1.0, ...array_fill(0, 127, 0.0)]))
        ->toThrow(FaceNotRecognizedException::class, 'Kemiripan hanya');
});

test('CipherSweet encrypts employee PII and supports blind index lookups', function () {
    $nik = '3276010101999999';
    $phone = '081299999999';

    $employeeId = createPostgresEmployee([
        'employee_number' => 'PG-CS1',
        'full_name' => 'CipherSweet Employee',
        'nik' => $nik,
        'phone' => $phone,
    ]);

    $employee = Employee::query()->findOrFail($employeeId);

    $raw = DB::table('employees')->where('id', $employee->id)->first(['nik', 'phone']);

    expect($raw->nik)->not->toBe($nik)
        ->and($raw->phone)->not->toBe($phone)
        ->and($employee->refresh()->nik)->toBe($nik)
        ->and($employee->phone)->toBe($phone)
        ->and(Employee::whereBlind('nik', 'nik_hash', $nik)->first()?->is($employee))->toBeTrue()
        ->and(Employee::whereBlind('phone', 'phone_hash', $phone)->first()?->is($employee))->toBeTrue();
});

test('PostgreSQL rejects employees managed by themselves', function () {
    $employeeId = createPostgresEmployee(['employee_number' => 'PG-SELF']);

    $this->expectException(QueryException::class);

    DB::table('employees')
        ->where('id', $employeeId)
        ->update(['parent_id' => $employeeId]);
});

test('PostgreSQL rejects future attendance dates', function () {
    $employeeId = createPostgresEmployee(['employee_number' => 'PG-FUTURE']);

    $current = DB::selectOne('SELECT CURRENT_DATE AS d')->d;
    $futureDate = CarbonImmutable::parse($current)->addDays(7)->toDateString();

    $this->expectException(QueryException::class);

    DB::table('attendances')->insert([
        'employee_id' => $employeeId,
        'date' => $futureDate,
        'status' => 'on_time',
        'is_wfa' => false,
        'late_minutes' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

test('payroll generation updates existing draft row instead of creating duplicate on PostgreSQL', function () {
    $this->seed(PayrollConfigSeeder::class);
    Cache::forget('tax_configs');
    Cache::forget('bpjs_configs');

    $employeeId = createPostgresEmployee(['employee_number' => 'PG-PAY1']);
    $employee = Employee::query()->with('position')->findOrFail($employeeId);
    $service = app(PayrollCalculatorService::class);

    $first = $service->generatePayroll($employee, '2026-06');
    $first->update(['pdf_path' => 'payslips/original.pdf']);

    $employee->position->update(['basic_salary' => 6_000_000]);
    $employee->load('position');

    $second = $service->generatePayroll($employee, '2026-06');

    expect($second->id)->toBe($first->id)
        ->and((float) $second->basic_salary)->toBe(6_000_000.0)
        ->and($second->pdf_path)->toBe('payslips/original.pdf')
        ->and(Payroll::query()->where('employee_id', $employeeId)->where('period', '2026-06')->count())->toBe(1);
});

test('payroll generation rejects existing published payroll on PostgreSQL', function () {
    $this->seed(PayrollConfigSeeder::class);
    Cache::forget('tax_configs');
    Cache::forget('bpjs_configs');

    $employeeId = createPostgresEmployee(['employee_number' => 'PG-PAY2']);
    $employee = Employee::query()->with('position')->findOrFail($employeeId);

    Payroll::query()->create([
        'employee_id' => $employeeId,
        'period' => '2026-06',
        'basic_salary' => 5_000_000,
        'total_allowance' => 0,
        'gross_salary' => 5_000_000,
        'overtime_pay' => 0,
        'pph21' => 0,
        'bpjs_health' => 0,
        'bpjs_employment' => 0,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 0,
        'net_salary' => 5_000_000,
        'status' => PayrollStatus::PUBLISHED,
    ]);

    expect(fn () => app(PayrollCalculatorService::class)->generatePayroll($employee, '2026-06'))
        ->toThrow(BusinessRuleException::class, 'sudah dikunci permanen');
});

test('pgvector stores 768D knowledge base embedding for cosine distance search', function () {
    $kbId = DB::table('knowledge_bases')->insertGetId([
        'knowledgeable_type' => Employee::class,
        'knowledgeable_id' => 1,
        'title' => 'Kebijakan Cuti',
        'content' => 'Karyawan berhak atas 12 hari cuti tahunan.',
        'category' => 'hr_policy',
        'status' => 'ready',
        'embedding' => new Vector(array_fill(0, 768, 0.1)),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('knowledge_bases')->insert([
        'knowledgeable_type' => Employee::class,
        'knowledgeable_id' => 2,
        'title' => 'Kebijakan BPJS',
        'content' => 'BPJS Kesehatan dan Ketenagakerjaan.',
        'category' => 'hr_policy',
        'status' => 'ready',
        'embedding' => new Vector(array_fill(0, 768, 0.9)),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $queryVector = new Vector(array_fill(0, 768, 0.11));
    $nearestId = DB::selectOne(<<<'SQL'
        SELECT id, embedding <=> ?::vector AS distance
        FROM knowledge_bases
        WHERE status = 'ready' AND embedding IS NOT NULL
        ORDER BY distance
        LIMIT 1
    SQL, [$queryVector])->id;

    expect($nearestId)->toBe($kbId);
});

test('pgvector nearest neighbor returns multiple results ordered by distance ascending', function () {
    $employeeNumbers = ['PG-RNK1', 'PG-RNK2', 'PG-RNK3'];

    $zero128 = fn () => array_fill(0, 128, 0.0);

    // RNK1: dim[0]=1.0, all others 0 (matches query)
    // RNK2: dim[0]=dim[1]=0.5, all others 0 (closer to query than RNK3)
    // RNK3: dim[2]=0.5, all others 0 (furthest from query)
    $vectors = [
        'PG-RNK1' => array_replace($zero128(), [0 => 1.0]),
        'PG-RNK2' => array_replace($zero128(), [0 => 0.5, 1 => 0.5]),
        'PG-RNK3' => array_replace($zero128(), [2 => 0.5]),
    ];

    foreach ($employeeNumbers as $num) {
        createPostgresEmployee([
            'employee_number' => $num,
            'face_embedding' => new Vector($vectors[$num]),
        ]);
    }

    // Query vector = same as RNK1
    $queryVector = new Vector(array_replace($zero128(), [0 => 1.0]));

    $results = DB::select(<<<'SQL'
        SELECT id, employee_number, face_embedding <=> ?::vector AS distance
        FROM employees
        WHERE employee_number IN ('PG-RNK1', 'PG-RNK2', 'PG-RNK3')
          AND face_embedding IS NOT NULL
        ORDER BY distance
    SQL, [$queryVector]);

    expect($results)->toHaveCount(3);
    expect($results[0]->employee_number)->toBe('PG-RNK1');
    expect((float) $results[0]->distance)->toBe(0.0);
    expect((float) $results[1]->distance)->toBeGreaterThan(0.0);
    expect((float) $results[2]->distance)->toBeGreaterThan((float) $results[1]->distance);
});

test('CipherSweet whereBlind returns null for non-existent value', function () {
    $result = Employee::whereBlind('nik', 'nik_hash', 'NONEXISTENT_NIK_000000')->first();
    expect($result)->toBeNull();
});

// ─── Migration Guard Tests ──────────────────────────────

test('employees table has vector type face_embedding column on PostgreSQL', function () {
    $column = DB::selectOne(<<<'SQL'
        SELECT data_type, udt_name
        FROM information_schema.columns
        WHERE table_name = 'employees'
          AND column_name = 'face_embedding'
    SQL);

    expect($column)->not->toBeNull();
    expect($column->udt_name)->toBe('vector');
});

test('knowledge_bases table has vector type embedding column on PostgreSQL', function () {
    $column = DB::selectOne(<<<'SQL'
        SELECT data_type, udt_name
        FROM information_schema.columns
        WHERE table_name = 'knowledge_bases'
          AND column_name = 'embedding'
    SQL);

    expect($column)->not->toBeNull();
    expect($column->udt_name)->toBe('vector');
});

test('knowledge_bases table has jsonb type metadata column on PostgreSQL', function () {
    $column = DB::selectOne(<<<'SQL'
        SELECT data_type
        FROM information_schema.columns
        WHERE table_name = 'knowledge_bases'
          AND column_name = 'metadata'
    SQL);

    expect($column)->not->toBeNull();
    expect($column->data_type)->toBe('jsonb');
});

test('jsonb metadata column stores and queries on PostgreSQL', function () {
    $id = DB::table('knowledge_bases')->insertGetId([
        'knowledgeable_type' => Employee::class,
        'knowledgeable_id' => 1,
        'title' => 'JSONB Test',
        'content' => 'Testing jsonb column',
        'category' => 'general',
        'status' => 'ready',
        'metadata' => json_encode(['source' => 'test', 'version' => 2]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $result = DB::table('knowledge_bases')
        ->where('id', $id)
        ->select('metadata')
        ->first();

    expect($result)->not->toBeNull();
    $meta = is_string($result->metadata) ? json_decode($result->metadata) : $result->metadata;
    expect($meta->source)->toBe('test');
    expect($meta->version)->toBe(2);
});

test('HNSW index exists on knowledge_bases embedding column', function () {
    $index = DB::selectOne(<<<'SQL'
        SELECT indexname, indexdef
        FROM pg_indexes
        WHERE tablename = 'knowledge_bases'
          AND indexdef LIKE '%hnsw%'
    SQL);

    expect($index)->not->toBeNull();
    expect($index->indexdef)->toContain('hnsw');
});

// ─── pg_trgm ────────────────────────────────────────────

test('pg_trgm similarity finds matching text in knowledge base content', function () {
    $targetId = DB::table('knowledge_bases')->insertGetId([
        'knowledgeable_type' => Employee::class,
        'knowledgeable_id' => 1,
        'title' => 'Kebijakan Cuti Tahunan',
        'content' => 'Setiap karyawan berhak atas 12 hari cuti tahunan.',
        'category' => 'hr_policy',
        'status' => 'ready',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('knowledge_bases')->insert([
        'knowledgeable_type' => Employee::class,
        'knowledgeable_id' => 2,
        'title' => 'Kebijakan BPJS',
        'content' => 'BPJS Kesehatan dan BPJS Ketenagakerjaan.',
        'category' => 'hr_policy',
        'status' => 'ready',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Use a longer phrase that has enough trigram overlap to exceed threshold
    $result = DB::selectOne(<<<'SQL'
        SELECT id, similarity(content, 'hari cuti tahunan') AS score
        FROM knowledge_bases
        WHERE content % 'hari cuti tahunan'
        ORDER BY score DESC
        LIMIT 1
    SQL);

    expect($result)->not->toBeNull();
    expect($result->id)->toBe($targetId);
    expect((float) $result->score)->toBeGreaterThan(0.3);
});

test('pg_trgm similarity returns empty when no match', function () {
    DB::table('knowledge_bases')->insert([
        'knowledgeable_type' => Employee::class,
        'knowledgeable_id' => 1,
        'title' => 'English Policy',
        'content' => 'This is an English document with no Indonesian words.',
        'category' => 'hr_policy',
        'status' => 'ready',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $result = DB::selectOne(<<<'SQL'
        SELECT id, similarity(content, 'xyzzy_nonexistent') AS score
        FROM knowledge_bases
        WHERE content % 'xyzzy_nonexistent'
        ORDER BY score DESC
        LIMIT 1
    SQL);

    expect($result)->toBeNull();
});

// ─── pgcrypto ────────────────────────────────────────────

test('pgcrypto gen_random_uuid returns a valid UUID', function () {
    $uuid = DB::selectOne('SELECT gen_random_uuid() AS uuid');

    expect($uuid)->not->toBeNull();
    expect($uuid->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

test('pgcrypto gen_random_uuid produces unique values', function () {
    $uuids = DB::select('SELECT gen_random_uuid() AS uuid FROM generate_series(1, 10)');

    $raw = array_map(fn ($r) => $r->uuid, $uuids);
    expect(count(array_unique($raw)))->toBe(10);
});

test('pgcrypto gen_salt produces a valid bcrypt salt string', function () {
    $salt = DB::selectOne("SELECT gen_salt('bf') AS salt");

    expect($salt)->not->toBeNull();
    expect($salt->salt)->toMatch('/^\$2a\$\d{2}\$/');
});

test('pgcrypto crypt produces a valid bcrypt hash', function () {
    $hash = DB::selectOne("SELECT crypt('password123', gen_salt('bf')) AS hash");

    expect($hash)->not->toBeNull();
    expect($hash->hash)->toMatch('/^\$2a\$\d{2}\$.{53}$/');
});

test('pgcrypto crypt verification matches known password', function () {
    $result = DB::selectOne(<<<'SQL'
        SELECT crypt('testpass', '$2a$08$00000000000000000000000000000000000000000') = '$2a$08$00000000000000000000000000000000000000000' AS matches
    SQL);

    expect($result)->not->toBeNull();
    expect((bool) $result->matches)->toBeTrue();
});

test('pgcrypto digest returns a SHA256 hex string', function () {
    $digest = DB::selectOne("SELECT encode(digest('hello', 'sha256'), 'hex') AS hash");

    expect($digest)->not->toBeNull();
    expect(strlen($digest->hash))->toBe(64);
    expect($digest->hash)->toMatch('/^[0-9a-f]{64}$/');
});

test('pgcrypto hmac produces keyed hash different from plain digest', function () {
    $hmac = DB::selectOne("SELECT encode(hmac('message', 'secretkey', 'sha256'), 'hex') AS hash");
    $plain = DB::selectOne("SELECT encode(digest('message', 'sha256'), 'hex') AS hash");

    expect($hmac)->not->toBeNull();
    expect(strlen($hmac->hash))->toBe(64);
    expect($hmac->hash)->not->toBe($plain->hash);
});

// ─── PgVector Cast ──────────────────────────────

test('PgVector cast formatVector returns empty brackets for empty array', function () {
    $service = app(EmbeddingService::class);

    $result = $service->formatVector([]);

    expect($result)->toBe('[]');
});

test('PgVector cast formatVector returns properly formatted vector string', function () {
    $service = app(EmbeddingService::class);

    $result = $service->formatVector([0.1, 0.2, 0.3]);

    expect($result)->toBe('[0.1,0.2,0.3]');
});

test('PgVector cast formatVector handles floats without integer coercion', function () {
    $service = app(EmbeddingService::class);

    $result = $service->formatVector([1.0, 0.0, -0.5]);

    expect($result)->toBe('[1,0,-0.5]');
});

// ─── EmbeddingService with pgvector ─────────────

test('EmbeddingService searchSimilar returns correct ordering via pgvector cosine distance', function () {
    $targetId = DB::table('knowledge_bases')->insertGetId([
        'knowledgeable_type' => Employee::class,
        'knowledgeable_id' => 1,
        'title' => 'Kebijakan Cuti Tahunan',
        'content' => 'Setiap karyawan berhak atas 12 hari cuti tahunan.',
        'category' => 'hr_policy',
        'status' => 'ready',
        'embedding' => new Vector(array_fill(0, 768, 0.1)),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('knowledge_bases')->insert([
        'knowledgeable_type' => Employee::class,
        'knowledgeable_id' => 2,
        'title' => 'Kebijakan BPJS',
        'content' => 'BPJS Kesehatan dan BPJS Ketenagakerjaan.',
        'category' => 'hr_policy',
        'status' => 'ready',
        'embedding' => new Vector(array_fill(0, 768, 0.9)),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $queryVector = array_fill(0, 768, 0.11);
    $service = app(EmbeddingService::class);

    $results = $service->searchSimilar($queryVector, topK: 5);

    expect($results)->toHaveCount(2);
    expect($results->first()->id)->toBe($targetId);
});

test('EmbeddingService searchSimilar returns empty when no matching status', function () {
    DB::table('knowledge_bases')->insert([
        'knowledgeable_type' => Employee::class,
        'knowledgeable_id' => 1,
        'title' => 'Draft Document',
        'content' => 'This is still processing.',
        'category' => 'hr_policy',
        'status' => 'processing',
        'embedding' => new Vector(array_fill(0, 768, 0.1)),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = app(EmbeddingService::class);
    $results = $service->searchSimilar(array_fill(0, 768, 0.5), topK: 5);

    expect($results)->toHaveCount(0);
});

// ─── EmbeddingService with pg_trgm ──────────────

test('EmbeddingService searchByKeyword returns matching results via pg_trgm similarity', function () {
    $targetId = DB::table('knowledge_bases')->insertGetId([
        'knowledgeable_type' => Employee::class,
        'knowledgeable_id' => 1,
        'title' => 'Kebijakan Cuti Tahunan',
        'content' => 'Setiap karyawan berhak atas 12 hari cuti tahunan.',
        'category' => 'hr_policy',
        'status' => 'ready',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('knowledge_bases')->insert([
        'knowledgeable_type' => Employee::class,
        'knowledgeable_id' => 2,
        'title' => 'Kebijakan BPJS',
        'content' => 'BPJS Kesehatan dan BPJS Ketenagakerjaan.',
        'category' => 'hr_policy',
        'status' => 'ready',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = app(EmbeddingService::class);
    $results = $service->searchByKeyword('cuti tahunan', topK: 5);

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($targetId);
});

test('EmbeddingService searchByKeyword returns empty for completely unrelated query', function () {
    DB::table('knowledge_bases')->insert([
        'knowledgeable_type' => Employee::class,
        'knowledgeable_id' => 1,
        'title' => 'English Policy',
        'content' => 'This is an English document with no Indonesian words.',
        'category' => 'hr_policy',
        'status' => 'ready',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = app(EmbeddingService::class);
    $results = $service->searchByKeyword('xyzzy_nonexistent');

    expect($results)->toHaveCount(0);
});

// ─── Additional pg_trgm ─────────────────────────

test('pg_trgm show_trgm returns array of trigrams', function () {
    $trgm = DB::selectOne("SELECT show_trgm('cuti') AS trigrams");

    expect($trgm)->not->toBeNull();
    $raw = is_string($trgm->trigrams) ? json_decode($trgm->trigrams) : $trgm->trigrams;
    expect($raw)->toBeArray();
    expect(count($raw))->toBeGreaterThanOrEqual(3);
});

test('pg_trgm word_similarity returns higher score for similar words', function () {
    $high = DB::selectOne("SELECT word_similarity('cuti', 'cuti') AS score");
    $low = DB::selectOne("SELECT word_similarity('cuti', 'makan') AS score");

    expect((float) $high->score)->toBe(1.0);
    expect((float) $low->score)->toBeLessThan(1.0);
});
