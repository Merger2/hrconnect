<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Http\Controllers\Controller;
use App\Models\ImportExportRun;
use App\Support\FileAccessService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadImportExportRunController extends Controller
{
    public function __invoke(ImportExportRun $run, FileAccessService $fileAccessService): StreamedResponse
    {
        $this->authorize('download', $run);

        abort_if($run->status !== 'completed' || blank($run->file_path), 404);

        return $fileAccessService->downloadRelativePath(
            $run->file_path,
            'Import Export Download',
            $run->resource.' '.$run->operation.' artifact'
        );
    }
}
