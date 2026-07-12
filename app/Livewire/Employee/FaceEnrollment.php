<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use App\Models\FaceDescriptor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class FaceEnrollment extends Component
{
    public bool $isEnrolled = false;

    public bool $isCapturing = false;

    public function mount(): void
    {
        $employee = Auth::user()->employee;

        if ($employee) {
            $this->isEnrolled = FaceDescriptor::where('employee_id', $employee->id)
                ->where('is_active', true)
                ->exists();
        }
    }

    public function saveFaceDescriptor(array $descriptor): void
    {
        if (empty($descriptor)) {
            return;
        }

        $employee = Auth::user()->employee;

        if (! $employee) {
            $this->dispatch('toast', variant: 'error', text: __('Akun Anda belum terhubung dengan data karyawan.'));

            return;
        }

        if (! in_array(count($descriptor), [128, 129], true)) {
            $this->dispatch('toast', variant: 'error', text: __('Data wajah tidak valid. Silakan coba lagi.'));

            return;
        }

        try {
            $metadata = ['source' => 'web'];

            if (count($descriptor) === 129) {
                $metadata['descriptor_type'] = 'geometry';
                $metadata['descriptor_version'] = $descriptor[0];
                $descriptor = array_slice($descriptor, 1);
            }

            // Store geometry descriptor coordinates only (128D).
            // Version prefix is metadata only — pgvector requires consistent dimensions.
            $vectorString = '['.implode(',', $descriptor).']';

            FaceDescriptor::updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'embedding' => $vectorString,
                    'is_active' => true,
                    'metadata' => $metadata,
                ]
            );

            $employee->forceFill(['face_embedding' => $vectorString])->save();

            $this->isEnrolled = true;
            $this->isCapturing = false;

            $this->dispatch('toast', variant: 'success', text: __('Face ID berhasil didaftarkan!'));
        } catch (\Exception $e) {
            Log::error('Face enrollment failed', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('toast', variant: 'error', text: __('Gagal menyimpan data wajah. Silakan coba lagi.'));
        }
    }

    public function removeFace(): void
    {
        $employee = Auth::user()->employee;

        if (! $employee) {
            return;
        }

        try {
            FaceDescriptor::where('employee_id', $employee->id)
                ->update(['is_active' => false]);

            $employee->forceFill(['face_embedding' => null])->save();

            $this->isEnrolled = false;

            $this->dispatch('toast', variant: 'success', text: __('Face ID berhasil dihapus.'));
        } catch (\Exception $e) {
            Log::error('Face removal failed', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('toast', variant: 'error', text: __('Gagal menghapus data wajah.'));
        }
    }

    public function startCapture(): void
    {
        $this->isCapturing = true;
    }

    public function cancelCapture(): void
    {
        $this->isCapturing = false;
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
        return view('livewire.employee.face-enrollment');
    }
}
