<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ProjectVisitEvidence;
use App\Support\AttachmentPathValidator;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProjectVisitEvidenceAttachmentController extends Controller
{
    public function show(ProjectVisitEvidence $evidence, AttachmentPathValidator $validator): Response
    {
        $this->authorize('downloadPhoto', $evidence);

        abort_unless($evidence->photo_disk === 'local' && $evidence->photo_path, 404);

        $path = (string) $evidence->photo_path;
        abort_unless($validator->isSafeRelativePath($path), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        $name = $validator->safeDownloadName($evidence->photo_original_name ?: 'visit-evidence-'.$evidence->id.'.jpg', 'visit-evidence');

        return Storage::disk('local')->download($path, $name, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
