<?php

use App\Http\Middleware\DeviceDetection;
use Illuminate\Http\Request;

function detect(callable $modifyRequest): array
{
    $request = Request::create('/test', 'GET');
    $modifyRequest($request);

    $middleware = new DeviceDetection;
    $middleware->handle($request, fn ($r) => response('ok'));

    return [
        'device_type' => $request->attributes->get('device_type'),
        'browser' => $request->attributes->get('browser'),
        'os' => $request->attributes->get('os'),
        'is_mobile' => $request->attributes->get('is_mobile'),
    ];
}

test('no user agent defaults to desktop unknown', function () {
    $result = detect(fn ($r) => $r->headers->set('User-Agent', ''));

    expect($result['device_type'])->toBe('desktop')
        ->and($result['browser'])->toBe('unknown')
        ->and($result['os'])->toBe('unknown')
        ->and($result['is_mobile'])->toBeFalse();
});

test('iPhone Safari detected as mobile iOS', function () {
    $result = detect(fn ($r) => $r->headers->set('User-Agent',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'));

    expect($result['device_type'])->toBe('mobile')
        ->and($result['browser'])->toBe('safari')
        ->and($result['os'])->toBe('ios')
        ->and($result['is_mobile'])->toBeTrue();
});

test('Android Chrome detected as mobile android', function () {
    $result = detect(fn ($r) => $r->headers->set('User-Agent',
        'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.6422.165 Mobile Safari/537.36'));

    expect($result['device_type'])->toBe('mobile')
        ->and($result['browser'])->toBe('chrome')
        ->and($result['os'])->toBe('android')
        ->and($result['is_mobile'])->toBeTrue();
});

test('iPad detected as tablet iOS', function () {
    $result = detect(fn ($r) => $r->headers->set('User-Agent',
        'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'));

    expect($result['device_type'])->toBe('tablet')
        ->and($result['browser'])->toBe('safari')
        ->and($result['os'])->toBe('ios')
        ->and($result['is_mobile'])->toBeFalse();
});

test('Windows Chrome detected as desktop chrome windows', function () {
    $result = detect(fn ($r) => $r->headers->set('User-Agent',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36'));

    expect($result['device_type'])->toBe('desktop')
        ->and($result['browser'])->toBe('chrome')
        ->and($result['os'])->toBe('windows')
        ->and($result['is_mobile'])->toBeFalse();
});

test('Windows Firefox detected as desktop firefox windows', function () {
    $result = detect(fn ($r) => $r->headers->set('User-Agent',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0'));

    expect($result['browser'])->toBe('firefox')
        ->and($result['os'])->toBe('windows');
});

test('Mac Chrome detected as desktop chrome mac', function () {
    $result = detect(fn ($r) => $r->headers->set('User-Agent',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36'));

    expect($result['os'])->toBe('mac')
        ->and($result['browser'])->toBe('chrome');
});

test('Edge detected correctly', function () {
    $result = detect(fn ($r) => $r->headers->set('User-Agent',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36 Edg/125.0.2535.85'));

    expect($result['browser'])->toBe('edge');
});

test('Opera detected correctly', function () {
    $result = detect(fn ($r) => $r->headers->set('User-Agent',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36 OPR/111.0.0.0'));

    expect($result['browser'])->toBe('opera');
});
