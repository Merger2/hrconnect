<?php

namespace App\Livewire\User;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\CloudFile;
use App\Support\CollaborationRealtime;
use App\Support\CollaborationWorkspaceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class CollaborationInbox extends Component
{
    use InteractsWithBanner;
    use WithFileUploads;

    #[Url(history: true)]
    public string $selectedThreadId = '';

    public string $search = '';

    public string $messageBody = '';

    public ?TemporaryUploadedFile $uploadedFile = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        if ($this->selectedThreadId !== '' && $this->threadVisibleTo($user, (int) $this->selectedThreadId)->exists()) {
            return;
        }

        $firstThreadId = $this->visibleThreads($user)->value('id');

        $this->selectedThreadId = $firstThreadId ? (string) $firstThreadId : '';
    }

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        $user = Auth::user();

        if (! $user || ! CollaborationRealtime::enabled() || $user->company_id === null) {
            return [];
        }

        return [
            'echo-private:collaboration.company.'.$user->company_id.',.collaboration.updated' => '$refresh',
        ];
    }

    public function selectThread(int $threadId): void
    {
        $thread = $this->threadVisibleTo(Auth::user(), $threadId)->first();

        abort_unless($thread, 404);

        $this->selectedThreadId = (string) $thread->id;
        $this->markThreadRead($thread);
    }

    public function postMessage(CollaborationWorkspaceService $collaboration): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'selectedThreadId' => ['required', 'integer', Rule::exists('chat_threads', 'id')],
            'messageBody' => ['required_without:uploadedFile', 'nullable', 'string', 'max:3000'],
            'uploadedFile' => ['nullable', 'file', 'max:12288', 'mimes:csv,doc,docx,jpeg,jpg,pdf,png,txt,webp,xls,xlsx'],
        ]);

        $thread = $this->threadVisibleTo($user, (int) $validated['selectedThreadId'])->first();

        abort_unless($thread, 404);

        $body = trim((string) ($validated['messageBody'] ?? ''));
        $fileData = null;

        if ($this->uploadedFile) {
            $originalName = basename($this->uploadedFile->getClientOriginalName());
            $fileData = [
                'path' => $this->uploadedFile->store(
                    'collaboration/'.(int) $thread->company_id.'/threads/'.$thread->id.'/'.Str::uuid(),
                    'local',
                ),
                'original_name' => $originalName,
                'mime_type' => $this->uploadedFile->getMimeType(),
                'size' => $this->uploadedFile->getSize(),
                'disk' => 'local',
            ];

            if ($body === '') {
                $body = __('Shared a file: :name', ['name' => $originalName]);
            }
        }

        $collaboration->postMessage($user, $thread, $body, $fileData);

        $this->reset(['messageBody', 'uploadedFile']);
        $this->markThreadRead($thread);
        $this->banner(__('Message sent.'));
    }

    public function render()
    {
        $user = Auth::user();
        $threads = $this->visibleThreads($user)
            ->with(['company:id,name', 'project:id,name', 'members:id,name,profile_photo_path'])
            ->withCount('messages')
            ->when($this->search !== '', fn (Builder $query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->latest()
            ->get();

        $selectedThread = $this->selectedThreadId === ''
            ? $threads->first()
            : $threads->firstWhere('id', (int) $this->selectedThreadId);

        if (! $selectedThread && $threads->isNotEmpty()) {
            $selectedThread = $threads->first();
            $this->selectedThreadId = (string) $selectedThread->id;
        }

        $messages = $selectedThread
            ? ChatMessage::query()
                ->with('user:id,name,profile_photo_path')
                ->where('chat_thread_id', $selectedThread->id)
                ->latest()
                ->limit(80)
                ->get()
                ->reverse()
                ->values()
            : collect();

        $files = $selectedThread
            ? CloudFile::query()
                ->where('chat_thread_id', $selectedThread->id)
                ->latest()
                ->get()
                ->filter(fn (CloudFile $file): bool => $user->can('download', $file))
                ->values()
            : collect();

        return view('livewire.user.collaboration-inbox', [
            'threads' => $threads,
            'selectedThread' => $selectedThread,
            'messages' => $messages,
            'files' => $files,
            'realtimeEnabled' => CollaborationRealtime::enabled(),
            'pollingEnabled' => ! $this->hasDraftMessage(),
            'pollInterval' => CollaborationRealtime::pollInterval(),
        ]);
    }

    private function visibleThreads($user): Builder
    {
        return ChatThread::query()
            ->whereHas('members', fn (Builder $query) => $query->whereKey($user->id))
            ->where(function (Builder $query) use ($user): void {
                $query->whereNull('company_id');

                if ($user->company_id !== null) {
                    $query->orWhere('company_id', $user->company_id);
                }
            });
    }

    private function threadVisibleTo($user, int $threadId): Builder
    {
        return $this->visibleThreads($user)->whereKey($threadId);
    }

    private function markThreadRead(ChatThread $thread): void
    {
        $thread->members()->updateExistingPivot(Auth::id(), [
            'last_read_at' => now(),
        ]);
    }

    private function hasDraftMessage(): bool
    {
        return trim($this->messageBody) !== '' || $this->uploadedFile instanceof TemporaryUploadedFile;
    }
}
