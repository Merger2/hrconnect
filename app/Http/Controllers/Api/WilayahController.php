<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wilayah;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class WilayahController extends Controller
{
    /**
     * Get list of provinces (kode length 2)
     */
    public function provinces(Request $request): JsonResponse
    {
        $search = $this->validatedSearch($request);

        return response()->json($this->remember('provinces', null, $search, function () use ($search) {
            $query = Wilayah::whereRaw('LENGTH(kode) = 2');

            return $this->arrayResults($this->applySearch($query, $search)->orderBy('nama')->get());
        }));
    }

    /**
     * Get list of regencies (kabupaten/kota) by province
     */
    public function regencies(Request $request, string $provinceCode): JsonResponse
    {
        abort_unless(preg_match('/^\d{2}$/', (string) $provinceCode) === 1, 404);

        $search = $this->validatedSearch($request);

        return response()->json($this->remember('regencies', $provinceCode, $search, function () use ($provinceCode, $search) {
            $query = Wilayah::where('kode', 'like', "{$provinceCode}.%")
                ->whereRaw('LENGTH(kode) = 5');

            return $this->arrayResults($this->applySearch($query, $search)->orderBy('nama')->get());
        }));
    }

    /**
     * Get list of districts (kecamatan) by regency
     */
    public function districts(Request $request, string $regencyCode): JsonResponse
    {
        abort_unless(preg_match('/^\d{2}\.\d{2}$/', (string) $regencyCode) === 1, 404);

        $search = $this->validatedSearch($request);

        return response()->json($this->remember('districts', $regencyCode, $search, function () use ($regencyCode, $search) {
            $query = Wilayah::where('kode', 'like', "{$regencyCode}.%")
                ->whereRaw('LENGTH(kode) = 8');

            return $this->arrayResults($this->applySearch($query, $search)->orderBy('nama')->get());
        }));
    }

    /**
     * Get list of villages (kelurahan/desa) by district
     */
    public function villages(Request $request, string $districtCode): JsonResponse
    {
        abort_unless(preg_match('/^\d{2}\.\d{2}\.\d{2}$/', (string) $districtCode) === 1, 404);

        $search = $this->validatedSearch($request);

        return response()->json($this->remember('villages', $districtCode, $search, function () use ($districtCode, $search) {
            $query = Wilayah::where('kode', 'like', "{$districtCode}.%")
                ->whereRaw('LENGTH(kode) = 13');

            return $this->arrayResults($this->applySearch($query, $search)->orderBy('nama')->get());
        }));
    }

    private function validatedSearch(Request $request): ?string
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:80', Rule::notIn(['%', '%%', '_'])],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        return $search !== '' ? $search : null;
    }

    private function applySearch(Builder $query, ?string $search): Builder
    {
        if ($search !== null) {
            $query->where('nama', 'like', '%'.addcslashes($search, '%_\\').'%');
        }

        return $query;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function remember(string $scope, ?string $code, ?string $search, Closure $callback): array
    {
        return Cache::remember(
            'wilayah:'.implode(':', [$scope, $code ?: 'all', sha1((string) $search)]),
            now()->addDay(),
            $callback
        );
    }

    /**
     * @param  EloquentCollection<int, Wilayah>  $wilayah
     * @return list<array<string, mixed>>
     */
    private function arrayResults(EloquentCollection $wilayah): array
    {
        return $wilayah
            ->map(fn (Wilayah $item): array => $item->getAttributes())
            ->values()
            ->all();
    }
}
