<?php

use App\Events\AnnouncementsChanged;
use App\Livewire\Admin\AnnouncementManager;
use App\Livewire\Shared\HighPriorityAnnouncementModal;
use App\Livewire\Shared\NotificationsDropdown;
use App\Models\Announcement;
use App\Models\User;
use App\Support\AnnouncementRefresh;
use App\Support\BroadcastRuntime;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

test('announcement manager broadcasts queued change events', function () {
    Event::fake([AnnouncementsChanged::class]);
    enableReverbBroadcasting();

    $superadmin = User::factory()->admin(true)->create();

    $this->actingAs($superadmin);

    Livewire::test(AnnouncementManager::class)
        ->set('title', 'High Priority Notice')
        ->set('content', 'Please review the new operational update.')
        ->set('priority', 'high')
        ->set('modal_behavior', 'acknowledge')
        ->set('publish_date', now()->format('Y-m-d'))
        ->set('expire_date', null)
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    Event::assertDispatched(AnnouncementsChanged::class, fn (AnnouncementsChanged $event): bool => $event->action === 'created');
});

test('announcement manager skips broadcast jobs on shared hosting fallback', function () {
    Event::fake([AnnouncementsChanged::class]);
    Config::set('broadcasting.default', 'log');

    $superadmin = User::factory()->admin(true)->create();

    $this->actingAs($superadmin);

    Livewire::test(AnnouncementManager::class)
        ->set('title', 'Shared Hosting Notice')
        ->set('content', 'This install uses polling fallback.')
        ->set('priority', 'high')
        ->set('modal_behavior', 'acknowledge')
        ->set('publish_date', now()->format('Y-m-d'))
        ->set('expire_date', null)
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    Event::assertNotDispatched(AnnouncementsChanged::class);
});

test('announcement refresh uses broadcast on vps and polling fallback on shared hosting', function () {
    Config::set('realtime.announcements.refresh_mode', 'auto');
    Config::set('realtime.announcements.broadcast_connections', ['reverb', 'pusher', 'ably']);

    Config::set('broadcasting.default', 'log');

    expect(AnnouncementRefresh::shouldPoll())->toBeTrue();

    enableReverbBroadcasting();

    expect(AnnouncementRefresh::shouldPoll())->toBeFalse();
});

test('announcement auto mode keeps polling when broadcast driver is not fully configured', function () {
    Config::set('broadcasting.default', 'reverb');
    Config::set('broadcasting.connections.reverb.key', null);
    Config::set('realtime.announcements.refresh_mode', 'auto');
    Config::set('realtime.announcements.broadcast_connections', ['reverb', 'pusher', 'ably']);

    expect(AnnouncementRefresh::broadcastingEnabled())->toBeFalse()
        ->and(AnnouncementRefresh::shouldPoll())->toBeTrue();
});

test('announcement refresh mode can force poll broadcast or off', function () {
    Config::set('broadcasting.default', 'log');

    Config::set('realtime.announcements.refresh_mode', 'poll');
    expect(AnnouncementRefresh::shouldPoll())->toBeTrue();

    Config::set('realtime.announcements.refresh_mode', 'broadcast');
    expect(AnnouncementRefresh::shouldPoll())->toBeFalse();

    Config::set('realtime.announcements.refresh_mode', 'off');
    expect(AnnouncementRefresh::shouldPoll())->toBeFalse();
});

test('announcement polling interval is sanitized for livewire modifiers', function () {
    Config::set('realtime.announcements.poll_interval', '120s');
    expect(AnnouncementRefresh::pollInterval())->toBe('120s');

    Config::set('realtime.announcements.poll_interval', 'bad value');
    expect(AnnouncementRefresh::pollInterval())->toBe('60s');
});

test('echo listeners are only registered when realtime broadcasting is enabled', function () {
    Config::set('realtime.announcements.refresh_mode', 'auto');
    Config::set('realtime.announcements.broadcast_connections', ['reverb', 'pusher', 'ably']);
    Config::set('broadcasting.default', 'log');

    expect(componentListeners(new HighPriorityAnnouncementModal))->toBe([])
        ->and(componentListeners(new NotificationsDropdown))
        ->not->toHaveKey('echo:announcements,.announcements.changed');

    enableReverbBroadcasting();

    expect(componentListeners(new HighPriorityAnnouncementModal))
        ->toHaveKey('echo:announcements,.announcements.changed')
        ->and(componentListeners(new NotificationsDropdown))
        ->toHaveKey('echo:announcements,.announcements.changed');
});

test('high priority acknowledgement can be dismissed with explicit button acknowledgement', function () {
    $user = User::factory()->create([
        'group' => 'user',
    ]);
    $creator = User::factory()->admin(true)->create();

    $announcement = Announcement::create([
        'title' => 'Critical Policy',
        'content' => 'Please acknowledge.',
        'priority' => 'high',
        'modal_behavior' => 'acknowledge',
        'published_at' => now(),
        'expired_at' => null,
        'is_active' => true,
        'created_by' => $creator->id,
    ]);

    $this->actingAs($user);

    Livewire::test(HighPriorityAnnouncementModal::class)
        ->assertSet('showModal', true)
        ->call('dismiss')
        ->assertSet('showModal', true)
        ->call('dismiss', true)
        ->assertSet('showModal', false);

    $this->assertDatabaseHas('announcement_user_views', [
        'announcement_id' => $announcement->id,
        'user_id' => $user->id,
    ]);
});

test('broadcast runtime exposes safe echo client config for local and production reverb', function () {
    enableReverbBroadcasting([
        'host' => '127.0.0.1',
        'port' => 8080,
        'scheme' => 'http',
    ]);

    $local = BroadcastRuntime::clientConfig(request());

    expect($local['enabled'])->toBeTrue()
        ->and($local['connection'])->toBe('reverb')
        ->and($local['authEndpoint'])->toBe('/broadcasting/auth')
        ->and($local['reverb']['host'])->toBe('127.0.0.1')
        ->and($local['reverb']['port'])->toBe(8080)
        ->and($local['reverb']['scheme'])->toBe('http');

    enableReverbBroadcasting([
        'host' => 'paspapan.example.com',
        'port' => 443,
        'scheme' => 'https',
    ]);

    $production = BroadcastRuntime::clientConfig(request());

    expect($production['enabled'])->toBeTrue()
        ->and($production['reverb']['host'])->toBe('paspapan.example.com')
        ->and($production['reverb']['port'])->toBe(443)
        ->and($production['reverb']['scheme'])->toBe('https');
});

test('broadcast auth accepts string user ids for private notification channels', function () {
    enableReverbBroadcasting();
    require base_path('routes/channels.php');

    $user = User::factory()->create([
        'group' => 'user',
    ]);

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-App.Models.User.'.$user->getKey(),
        ])
        ->assertOk()
        ->assertJsonStructure(['auth']);
});

function componentListeners(object $component): array
{
    $method = new ReflectionMethod($component, 'getListeners');
    $method->setAccessible(true);

    return $method->invoke($component);
}

/**
 * @param  array{host?: string, port?: int, scheme?: string}  $options
 */
function enableReverbBroadcasting(array $options = []): void
{
    Config::set('broadcasting.default', 'reverb');
    Config::set('broadcasting.connections.reverb.app_id', 'test-reverb-app');
    Config::set('broadcasting.connections.reverb.key', 'test-reverb-key');
    Config::set('broadcasting.connections.reverb.secret', 'test-reverb-secret');
    Config::set('broadcasting.connections.reverb.options.host', $options['host'] ?? '127.0.0.1');
    Config::set('broadcasting.connections.reverb.options.port', $options['port'] ?? 8080);
    Config::set('broadcasting.connections.reverb.options.scheme', $options['scheme'] ?? 'http');
}

test('announcement event uses public announcement channel payload', function () {
    $event = new AnnouncementsChanged('updated');

    expect($event->broadcastAs())->toBe('announcements.changed')
        ->and($event->broadcastWith())->toBe(['action' => 'updated'])
        ->and($event->broadcastOn()->name)->toBe('announcements');
});
