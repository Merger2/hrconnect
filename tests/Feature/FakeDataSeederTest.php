<?php

use App\Models\JobTitle;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('database seeder is idempotent for real master data', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', 'like', 'employee%@hrconnect.local')->count())->toBe(50)
        ->and(JobTitle::query()->whereIn('name', ['Head', 'Manager', 'Senior', 'Officer', 'Staff'])->whereNotNull('job_level_id')->count())->toBe(5);
});
