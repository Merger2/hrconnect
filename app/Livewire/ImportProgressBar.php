<?php

namespace App\Livewire;

use App\Models\ImportProgress;
use Livewire\Attributes\On;
use Livewire\Component;

class ImportProgressBar extends Component
{
    public int $progressId = 0;

    public string $status = '';

    public int $percentage = 0;

    public string $label = '';

    public function render()
    {
        return view('livewire.import-progress-bar');
    }

    #[On('start-import')]
    public function startImport(int $progressId, string $label = ''): void
    {
        $this->progressId = $progressId;
        $this->label = $label;
        $this->pollProgress();
    }

    public function pollProgress(): void
    {
        if ($this->progressId <= 0) {
            return;
        }

        $progress = ImportProgress::find($this->progressId);

        if (! $progress) {
            $this->progressId = 0;
            $this->status = '';
            $this->percentage = 0;
            $this->label = '';

            return;
        }

        $this->status = $progress->status;
        $this->percentage = $progress->percentage();
        $this->label = $this->label ?: "Mengimpor {$progress->type}...";

        if ($progress->isCompleted() || $progress->isFailed()) {
            $this->dispatch('import-completed', status: $progress->status, errors: $progress->errors);

            if ($progress->isFailed()) {
                $this->dispatch('notify', type: 'error', message: 'Import gagal: '.($progress->errors ?? 'Unknown error'));
            } else {
                $this->dispatch('notify', type: 'success', message: 'Import selesai!');
            }
        }
    }
}
