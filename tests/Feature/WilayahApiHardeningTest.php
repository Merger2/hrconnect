<?php

use App\Models\Wilayah;
use Illuminate\Support\Facades\Cache;

test('wilayah api validates dotted numeric route parameters and search input', function () {
    Wilayah::query()->insert([
        ['kode' => '31', 'nama' => 'DKI Jakarta'],
        ['kode' => '31.01', 'nama' => 'Jakarta Selatan'],
        ['kode' => '31.01.01', 'nama' => 'Kebayoran Baru'],
        ['kode' => '31.01.01.1001', 'nama' => 'Melawai'],
    ]);

    $this->getJson('/api/v1/wilayah/regencies/31')
        ->assertOk()
        ->assertJsonFragment(['kode' => '31.01']);

    $this->getJson('/api/v1/wilayah/regencies/31foo')->assertNotFound();
    $this->getJson('/api/v1/wilayah/districts/31.01foo')->assertNotFound();
    $this->getJson('/api/v1/wilayah/villages/31.01.01foo')->assertNotFound();

    $this->getJson('/api/v1/wilayah/provinces?search='.str_repeat('a', 81))
        ->assertUnprocessable();
});

test('wilayah api caches repeated responses without leaking stack traces', function () {
    Cache::flush();

    Wilayah::query()->insert([
        ['kode' => '32', 'nama' => 'Jawa Barat'],
        ['kode' => '32.01', 'nama' => 'Bogor'],
    ]);

    $this->getJson('/api/v1/wilayah/regencies/32?search=Bogor')
        ->assertOk()
        ->assertJsonFragment(['kode' => '32.01']);

    Wilayah::query()->where('kode', '32.01')->delete();

    $this->getJson('/api/v1/wilayah/regencies/32?search=Bogor')
        ->assertOk()
        ->assertJsonFragment(['kode' => '32.01'])
        ->assertDontSee('Exception');
});
