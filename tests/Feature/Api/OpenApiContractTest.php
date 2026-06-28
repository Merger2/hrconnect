<?php

use Illuminate\Support\Facades\Route;

function openApiSpec(): array
{
    static $spec = null;

    if ($spec === null) {
        $path = base_path('docs/api/api.json');

        if (! file_exists($path)) {
            throw new RuntimeException('OpenAPI spec not found, run: php artisan scramble:export');
        }

        $spec = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($spec) || ! isset($spec['openapi'], $spec['paths'])) {
            throw new RuntimeException('OpenAPI spec malformed');
        }
    }

    return $spec;
}

function urisMatch(string $specUri, string $actualUri): bool
{
    $spec = preg_replace('#\{[^}]+\}#', '__PARAM__', $specUri);
    $pattern = preg_quote($spec, '#');
    $pattern = str_replace('__PARAM__', '[^/]+', $pattern);

    return (bool) preg_match('#^'.$pattern.'$#', $actualUri);
}

function getRouteList(): array
{
    $routes = Route::getRoutes()->getRoutesByMethod();
    $result = [];

    $skipUris = [
        'api/v1/email/verify/{id}/{hash}', // Scramble limitation: public route not auto-documented
        'api/v1/email/resend', // Scramble limitation: auth-only route not auto-documented

    ];

    foreach ($routes as $method => $routeGroup) {
        // Skip HEAD routes — automatically added by Laravel for GET, not in spec
        if (strtoupper($method) === 'HEAD') {
            continue;
        }

        foreach ($routeGroup as $route) {
            $uri = $route->uri();
            if (! str_starts_with($uri, 'api/v1/') && $uri !== 'api/v1/health') {
                continue;
            }
            if (in_array($uri, $skipUris, true)) {
                continue;
            }
            $result[] = ['method' => strtoupper($method), 'uri' => '/'.$uri];
        }
    }

    return $result;
}

function getSpecRoutes(): array
{
    $spec = openApiSpec();
    $result = [];

    foreach ($spec['paths'] as $path => $methods) {
        foreach ($methods as $method => $def) {
            if (in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                $result[] = ['method' => strtoupper($method), 'uri' => '/api'.$path];
            }
        }
    }

    return $result;
}

// ─── Spec Structure ──────────────────────────────────────────────────

test('OpenAPI spec is valid JSON and uses version 3.1', function () {
    $spec = openApiSpec();

    expect($spec['openapi'])->toBe('3.1.0');
});

test('info section has correct title and version', function () {
    $spec = openApiSpec();

    expect($spec['info']['title'])->toBe('HRConnect API');
    expect($spec['info']['version'])->toBe('1.0.0');
});

test('description mentions Bearer token authentication', function () {
    $spec = openApiSpec();

    expect($spec['info']['description'])->toContain('Bearer');
});

test('server URL uses /api prefix', function () {
    $spec = openApiSpec();

    expect($spec['servers'][0]['url'])->toEndWith('/api');
});

test('all 12 API tags are defined', function () {
    $spec = openApiSpec();
    $tags = array_column($spec['tags'], 'name');
    sort($tags);

    expect($tags)->toBe([
        'Approvals',
        'Attendance',
        'Auth',
        'Employees',
        'Face Recognition',
        'Health',
        'Knowledge Base',
        'Leave',
        'Overtime',
        'Payroll',
        'Profile',
        'Reimbursement',
    ]);
});

test('bearer security scheme is defined', function () {
    $spec = openApiSpec();

    expect($spec['components']['securitySchemes']['http'])
        ->toMatchArray([
            'type' => 'http',
            'scheme' => 'bearer',
        ]);
});

test('global security applies bearer by default', function () {
    $spec = openApiSpec();

    expect($spec['security'] ?? [])->toContain(['http' => []]);
});

test('all 4 reusable error responses are defined', function () {
    $spec = openApiSpec();

    expect($spec['components']['responses'] ?? [])->toHaveKeys([
        'AuthenticationException',
        'AuthorizationException',
        'ModelNotFoundException',
        'ValidationException',
    ]);
});

test('28 component schemas are defined', function () {
    $spec = openApiSpec();

    expect(count($spec['components']['schemas'] ?? []))->toBe(28);
});

test('44 path templates with 55 operations are documented', function () {
    $spec = openApiSpec();
    $paths = $spec['paths'];

    expect(count($paths))->toBe(44);

    $ops = 0;
    foreach ($paths as $methods) {
        foreach ($methods as $method => $def) {
            if (in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'])) {
                $ops++;
            }
        }
    }

    expect($ops)->toBe(55);
});

// ─── Route Completeness ──────────────────────────────────────────────

test('all actual API routes are documented in the spec', function () {
    $actualRoutes = getRouteList();
    $specRoutes = getSpecRoutes();

    foreach ($actualRoutes as $actual) {
        $found = false;

        foreach ($specRoutes as $spec) {
            if ($spec['method'] === $actual['method'] && urisMatch($spec['uri'], $actual['uri'])) {
                $found = true;
                break;
            }
        }

        expect($found)->toBeTrue(
            "Route {$actual['method']} {$actual['uri']} is NOT documented in OpenAPI spec"
        );
    }
});

test('no undocumented routes exist in the spec', function () {
    $actualRoutes = getRouteList();
    $specRoutes = getSpecRoutes();

    foreach ($specRoutes as $spec) {
        $found = false;

        foreach ($actualRoutes as $actual) {
            if ($spec['method'] === $actual['method'] && urisMatch($spec['uri'], $actual['uri'])) {
                $found = true;
                break;
            }
        }

        expect($found)->toBeTrue(
            "Spec documents {$spec['method']} {$spec['uri']} but no actual route exists"
        );
    }
});

// ─── Security Contract ──────────────────────────────────────────────

test('4 public routes have empty security array', function () {
    $spec = openApiSpec();

    $publicPaths = ['/v1/health', '/v1/auth/login', '/v1/auth/2fa/challenge', '/v1/auth/forgot-password'];

    foreach ($publicPaths as $path) {
        foreach ($spec['paths'][$path] ?? [] as $method => $def) {
            if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                continue;
            }

            expect($def['security'] ?? null)->toBe(
                [],
                "{$method} {$path} should have security: []"
            );
        }
    }
});

test('all protected routes document 401 response via ref', function () {
    $spec = openApiSpec();

    $publicPaths = ['/v1/health', '/v1/auth/login', '/v1/auth/2fa/challenge', '/v1/auth/forgot-password'];

    foreach ($spec['paths'] as $path => $methods) {
        if (in_array($path, $publicPaths)) {
            continue;
        }

        foreach ($methods as $method => $def) {
            if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                continue;
            }

            $responses = $def['responses'] ?? [];

            expect(isset($responses['401']))->toBeTrue(
                "{$method} {$path} is protected but missing 401 response"
            );

            $ref = $responses['401']['$ref'] ?? '';
            expect($ref)->toEndWith('/AuthenticationException');
        }
    }
});

// ─── Validation Contract ────────────────────────────────────────────

test('all endpoints with requestBody document 422 validation response', function () {
    $spec = openApiSpec();

    foreach ($spec['paths'] as $path => $methods) {
        foreach ($methods as $method => $def) {
            if (! in_array($method, ['post', 'put'])) {
                continue;
            }

            if (! isset($def['requestBody'])) {
                continue;
            }

            $responses = $def['responses'] ?? [];

            expect(isset($responses['422']))->toBeTrue(
                "{$method} {$path} has requestBody but missing 422 response"
            );

            $ref = $responses['422']['$ref'] ?? '';
            expect($ref)->toEndWith('/ValidationException');
        }
    }
});

// ─── Pagination Contract ────────────────────────────────────────────

test('all 7 list endpoints document pagination meta with 4 required fields', function () {
    $spec = openApiSpec();

    $listEndpoints = [
        'get' => ['/v1/approvals/pending', '/v1/attendance', '/v1/employees', '/v1/leave', '/v1/overtime', '/v1/payroll', '/v1/reimbursement'],
    ];

    foreach ($listEndpoints as $method => $paths) {
        foreach ($paths as $path) {
            $def = $spec['paths'][$path][$method] ?? null;
            expect($def)->not->toBeNull("{$method} {$path} not found in spec");

            $response = $def['responses']['200'] ?? [];
            $schema = $response['content']['application/json']['schema'] ?? [];
            $props = $schema['properties'] ?? [];

            expect(isset($props['meta']))->toBeTrue("{$method} {$path} missing meta");
            $metaProps = $props['meta']['properties'] ?? [];

            expect(isset($metaProps['current_page']))->toBeTrue("{$method} {$path} meta missing current_page");
            expect(isset($metaProps['last_page']))->toBeTrue("{$method} {$path} meta missing last_page");
            expect(isset($metaProps['per_page']))->toBeTrue("{$method} {$path} meta missing per_page");
            expect(isset($metaProps['total']))->toBeTrue("{$method} {$path} meta missing total");
        }
    }
});

// ─── Consistency ─────────────────────────────────────────────────────

test('all operationIds are unique and follow resource.verb convention', function () {
    $spec = openApiSpec();
    $ids = [];

    foreach ($spec['paths'] as $path => $methods) {
        foreach ($methods as $method => $def) {
            if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                continue;
            }

            $id = $def['operationId'] ?? null;
            expect($id)->not->toBeNull("Missing operationId on {$method} {$path}");

            expect($ids)->not->toContain($id, "Duplicate operationId: {$id}");
            $ids[] = $id;

            expect($id)->toMatch(
                '/^[a-z][a-z0-9]*(\.[a-z0-9]+)*(-[a-z0-9]+)?$/',
                "operationId '{$id}' on {$method} {$path} should follow resource.verb convention"
            );
        }
    }
});

test('no duplicate HTTP methods on any path', function () {
    $spec = openApiSpec();

    foreach ($spec['paths'] as $path => $methods) {
        $httpMethods = array_keys($methods);
        $httpMethods = array_intersect($httpMethods, ['get', 'post', 'put', 'patch', 'delete']);

        expect($httpMethods)->toHaveCount(
            count(array_unique($httpMethods)),
            "Duplicate HTTP methods on {$path}"
        );
    }
});

test('all paths have summary and tags', function () {
    $spec = openApiSpec();

    foreach ($spec['paths'] as $path => $methods) {
        foreach ($methods as $method => $def) {
            if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                continue;
            }

            expect(isset($def['summary']))->toBeTrue(
                "{$method} {$path} missing summary"
            );

            expect($def['tags'] ?? [])->not->toBeEmpty(
                "{$method} {$path} missing tags"
            );
        }
    }
});

test('rate limiting (429) is not documented yet — known gap from S-5 audit', function () {
    $spec = openApiSpec();
    $count = 0;

    foreach ($spec['paths'] as $path => $methods) {
        foreach ($methods as $method => $def) {
            if (isset($def['responses']['429'])) {
                $count++;
            }
        }
    }

    expect($count)->toBe(0, '429 responses should not exist yet — see S-5 for throttle audit');
});

// ─── Smoke Contract Tests ────────────────────────────────────────────

test('health endpoint returns ok or degraded', function () {
    $response = $this->get('/api/v1/health');

    $response->assertOk();
    $response->assertJsonStructure(['status']);
    expect($response->json('status'))->toBeIn(['ok', 'degraded']);
});

test('protected endpoint without auth returns 401 with message', function () {
    $response = $this->getJson('/api/v1/user');

    $response->assertUnauthorized();
    $response->assertJsonStructure(['message']);
});

test('protected endpoint with invalid token returns 401', function () {
    $response = $this->withHeaders(['Authorization' => 'Bearer invalid-token'])
        ->getJson('/api/v1/user');

    $response->assertUnauthorized();
});

test('login endpoint returns expected shape on validation failure', function () {
    $response = $this->postJson('/api/v1/auth/login', []);

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);
});

test('non-existent route returns 404', function () {
    $response = $this->getJson('/api/v1/nonexistent');

    $response->assertNotFound();
});
