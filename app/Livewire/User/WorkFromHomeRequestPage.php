<?php

namespace App\Livewire\User;

use App\Models\WorkFromHomeRequest;
use App\Support\WorkFromHomeRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Livewire\WithPagination;

class WorkFromHomeRequestPage extends Component
{
    use AuthorizesRequests;
    use InteractsWithBanner;
    use WithPagination;

    protected WorkFromHomeRequestService $wfhRequests;

    public string $date = '';

    public string $startTime = '';

    public string $endTime = '';

    public string $locationAddress = '';

    public string $reason = '';

    public bool $showCreateModal = false;

    public function boot(WorkFromHomeRequestService $wfhRequests): void
    {
        $this->wfhRequests = $wfhRequests;
    }

    public function mount(): void
    {
        $this->authorize('viewAny', WorkFromHomeRequest::class);
    }

    public function create(): void
    {
        $this->authorize('create', WorkFromHomeRequest::class);

        $this->resetValidation();
        $this->date = $this->date ?: Carbon::today()->toDateString();
        $this->showCreateModal = true;
    }

    public function close(): void
    {
        $this->reset(['date', 'startTime', 'endTime', 'locationAddress', 'reason']);
        $this->resetValidation();
        $this->showCreateModal = false;
    }

    public function submit(): void
    {
        $this->authorize('create', WorkFromHomeRequest::class);

        $validated = $this->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'startTime' => ['nullable', 'required_with:endTime', 'date_format:H:i'],
            'endTime' => ['nullable', 'required_with:startTime', 'date_format:H:i', 'after:startTime'],
            'locationAddress' => ['nullable', 'string', 'max:500'],
            'reason' => ['required', 'string', 'min:10', 'max:1200'],
        ]);

        $this->wfhRequests->submit(auth()->user(), [
            'date' => $validated['date'],
            'start_time' => $validated['startTime'] ?: null,
            'end_time' => $validated['endTime'] ?: null,
            'location_address' => $validated['locationAddress'] ?: null,
            'reason' => $validated['reason'],
            'metadata' => [
                'source' => 'user_wfh_request_page',
                'submitted_via' => request()->header('X-Paspapan-Client', 'web'),
            ],
        ]);

        $this->close();
        $this->banner(__('WFH request submitted.'));
    }

    public function render()
    {
        return view('livewire.user.work-from-home-request-page', [
            'requests' => $this->wfhRequests->queryForUser(auth()->user())->paginate(10),
        ])->layout('layouts.app');
    }
}
