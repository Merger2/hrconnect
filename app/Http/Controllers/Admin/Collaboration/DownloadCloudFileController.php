<?php

namespace App\Http\Controllers\Admin\Collaboration;

use App\Http\Controllers\Controller;
use App\Models\CloudFile;
use App\Support\AttachmentPathValidator;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DownloadCloudFileController extends Controller
{
    public function __invoke(CloudFile $file, AttachmentPathValidator $validator): Response
    {
        $this->authorize('download', $file);

        abort_unless($validator->isSafeRelativePath($file->path), 404);
        abort_unless($file->disk === 'local', 404);
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $validator->safeDownloadName($file->original_name ?: basename($file->path), 'cloud-file'), [
            'Cache-Control' => 'no-store, private',
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
