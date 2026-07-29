<?php

namespace App\Support;

use App\Models\ImportExportRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ImportExportRunViewService
{
    /**
     * @param  array<int, string>  $resources
     * @return array<int, array<string, mixed>>
     */
    public function recentForResources(array $resources, ?User $requester = null, int $limit = 8): array
    {
        $runs = ImportExportRun::query()
            ->with('requestedBy:id,name')
            ->whereIn('resource', $resources)
            ->when($requester, fn (Builder $query) => $query->where('requested_by_user_id', $requester->id))
            ->latest('id')
            ->limit($limit)
            ->get();

        return $runs->map(fn (ImportExportRun $run): array => $this->present($run))->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function present(ImportExportRun $run): array
    {
        return [
            'id' => $run->id,
            'resource' => $run->resource,
            'operation' => $run->operation,
            'status' => $run->status,
            'progress_percentage' => (int) $run->progress_percentage,
            'processed_rows' => (int) $run->processed_rows,
            'total_rows' => $run->total_rows,
            'file_name' => $run->file_name,
            'download_url' => $run->status === 'completed' && $run->file_path
                ? route('admin.import-export.runs.download', $run)
                : null,
            'size_human' => $run->size_bytes ? $this->humanBytes($run->size_bytes) : null,
            'requested_by' => $run->requestedBy?->name,
            'error_message' => $run->error_message,
            'created_at_human' => $run->created_at?->diffForHumans(),
            'updated_at_human' => $run->updated_at?->diffForHumans(),
            'started_at_human' => $run->started_at?->diffForHumans(),
            'completed_at_human' => $run->completed_at?->diffForHumans(),
            'label' => Str::headline($run->resource.' '.$run->operation),
        ];
    }

    private function humanBytes(int $bytes): string
    {
        return number_format($bytes / 1024 / 1024, 2).' MB';
    }
}
