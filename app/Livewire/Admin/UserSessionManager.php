<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class UserSessionManager extends Component
{
    use InteractsWithBanner;

    public string $search = '';

    public ?int $selectedUserId = null;

    public function boot(): void
    {
        if (! auth()->user()?->allowsAdminPermission('admin.user_sessions.manage')) {
            abort(403);
        }
    }

    public function selectUser(int $userId): void
    {
        $target = User::query()->findOrFail($userId);

        if ($target->isSuperadmin && ! auth()->user()->isSuperadmin) {
            abort(403);
        }

        $this->selectedUserId = $userId;
    }

    public function forgetSession(string $sessionId): void
    {
        $this->authorizeSelectedUser();

        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $this->selectedUserId)
            ->delete();

        $this->banner(__('Browser session disconnected.'));
    }

    public function forgetAllSessions(): void
    {
        $this->authorizeSelectedUser();

        DB::table('sessions')
            ->where('user_id', $this->selectedUserId)
            ->where('id', '!=', session()->getId())
            ->delete();

        $this->banner(__('All browser sessions disconnected.'));
    }

    public function revokeApiToken(string $tokenId): void
    {
        $this->authorizeSelectedUser();

        $user = User::query()->findOrFail($this->selectedUserId);
        $user->tokens()->whereKey($tokenId)->delete();

        $this->banner(__('API token revoked.'));
    }

    private function authorizeSelectedUser(): void
    {
        if ($this->selectedUserId === null) {
            abort(403);
        }

        $target = User::query()->findOrFail($this->selectedUserId);

        if ($target->isSuperadmin && ! auth()->user()->isSuperadmin) {
            abort(403);
        }
    }

    public function render()
    {
        $sessionsAvailable = config('session.driver') === 'database';

        $users = $sessionsAvailable
            ? User::query()
                ->with('company:id,name')
                ->when($this->search !== '', fn ($query) => $query->where(function ($nested): void {
                    $nested->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                }))
                ->where(function ($query): void {
                    $query->whereExists(fn ($exists) => $exists->select(DB::raw(1))
                        ->from('sessions')
                        ->whereColumn('sessions.user_id', 'users.id'))
                        ->orWhereHas('tokens');
                })
                ->orderBy('name')
                ->get()
                ->map(function (User $user): User {
                    $user->setAttribute('active_sessions_count', DB::table('sessions')
                        ->where('user_id', $user->id)
                        ->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime', 120))->getTimestamp())
                        ->count());
                    $user->setAttribute('active_api_tokens_count', $user->tokens()->count());

                    return $user;
                })
            : collect();

        $selectedUser = $this->selectedUserId !== null
            ? User::query()->find($this->selectedUserId)
            : null;

        $activeSessions = $selectedUser
            ? DB::table('sessions')
                ->where('user_id', $selectedUser->id)
                ->orderByDesc('last_activity')
                ->get()
                ->map(fn ($session) => [
                    'id' => $session->id,
                    'user_agent' => $session->user_agent,
                    'ip_address' => $session->ip_address,
                    'last_activity' => Carbon::createFromTimestamp($session->last_activity),
                    'is_current_device' => $session->id === session()->getId(),
                ])
            : collect();

        $apiTokens = $selectedUser
            ? $selectedUser->tokens()->get()->map(fn ($token) => [
                'id' => (string) $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at ? Carbon::parse($token->last_used_at) : null,
                'expires_at' => $token->expires_at ? Carbon::parse($token->expires_at) : null,
                'abilities' => $token->abilities ?? [],
            ])
            : collect();

        return view('livewire.admin.user-session-manager', [
            'sessionsAvailable' => $sessionsAvailable,
            'users' => $users,
            'selectedUser' => $selectedUser,
            'activeSessions' => $activeSessions,
            'apiTokens' => $apiTokens,
        ]);
    }
}
