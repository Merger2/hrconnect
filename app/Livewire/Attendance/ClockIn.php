<?php

declare(strict_types=1);

namespace App\Livewire\Attendance;

use App\Services\FaceRecognitionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ClockIn extends Component
{
    public bool $hasFaceEnrolled = false;

    public function mount(): void
    {
        $employee = Auth::user()?->employee;

        $this->hasFaceEnrolled = $employee
            ? app(FaceRecognitionService::class)->hasFaceEnrolled($employee)
            : false;
    }

    public function saveFaceDescriptor(array $descriptor): void
    {
        $employee = Auth::user()?->employee;
        if ($employee) {
            app(FaceRecognitionService::class)->saveFaceDescriptor($employee, $descriptor);
            $this->hasFaceEnrolled = true;
            $this->dispatch('face-enrolled-successfully');
        }
    }

    public function reportClientError(string $stage, string $message, array $context = []): void
    {
        $user = Auth::user();

        Log::warning('Face enrollment client error', [
            'user_id' => $user?->id,
            'email' => $user?->email,
            'stage' => $stage,
            'message' => $message,
            'context' => $context,
            'route' => request()->path(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function render()
    {
        return view('attendance.clock-in');
    }
}
