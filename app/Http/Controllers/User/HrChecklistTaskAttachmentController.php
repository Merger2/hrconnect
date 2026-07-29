<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\HrChecklistTask;
use App\Support\AttachmentPathValidator;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class HrChecklistTaskAttachmentController extends Controller
{
    public function show(HrChecklistTask $task, AttachmentPathValidator $validator): Response
    {
        $this->authorize('downloadAttachment', $task);

        abort_unless($task->attachment_path, 404);

        $path = (string) $task->attachment_path;
        abort_unless($validator->isSafeRelativePath($path), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        $name = $validator->safeDownloadName($task->attachment_original_name ?: basename($path), 'hr-task-attachment');

        return Storage::disk('local')->download($path, $name, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
