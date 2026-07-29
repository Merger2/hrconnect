<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Reimbursement;
use App\Support\FileAccessService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReimbursementAttachmentController extends Controller
{
    public function __construct(
        protected FileAccessService $fileAccessService,
    ) {}

    public function show(Reimbursement $reimbursement): StreamedResponse
    {
        $this->authorize('view', $reimbursement);

        $path = $reimbursement->attachment;

        if (! $path) {
            abort(404);
        }

        return $this->fileAccessService->streamRelativePath(
            $path,
            'Reimbursement Attachment Viewed',
            'Viewed reimbursement attachment'
        );
    }
}
