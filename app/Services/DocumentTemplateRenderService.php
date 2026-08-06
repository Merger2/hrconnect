<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeDocumentTemplate;
use Illuminate\Support\Facades\View;

final class DocumentTemplateRenderService
{
    /**
     * Render template jadi HTML pakai data employee + request.
     */
    public function renderHtml(EmployeeDocumentTemplate $template, Employee $employee, ?EmployeeDocumentRequest $request = null): string
    {
        $variables = $this->buildVariables($employee, $request);

        $header = $this->renderHeader($template);
        $body = $this->interpolate($template->content, $variables);
        $footer = $template->footer ?? '';

        return View::make('documents.employee-template', [
            'paperSize' => $template->paper_size,
            'orientation' => $template->orientation,
            'header' => $header,
            'body' => $body,
            'footer' => $footer,
        ])->render();
    }

    /**
     * Build variabel yang bisa dipakai di template (placeholder {{$nama}}).
     */
    public function buildVariables(Employee $employee, ?EmployeeDocumentRequest $request = null): array
    {
        $user = $employee->user;

        return [
            'employee_name' => $employee->full_name ?? $user->name ?? '-',
            'employee_nip' => $employee->nip ?? '-',
            'employee_position' => $employee->position->title ?? '-',
            'employee_division' => $employee->division->name ?? '-',
            'employee_join_date' => $employee->hire_date?->format('d M Y') ?? '-',
            'company_name' => config('app.name'),
            'request_purpose' => $request->purpose ?? '-',
            'request_date' => $request?->created_at?->format('d M Y') ?? now()->format('d M Y'),
            'current_date' => now()->format('d M Y'),
        ];
    }

    private function interpolate(?string $template, array $variables): string
    {
        if (blank($template)) {
            return '';
        }

        return preg_replace_callback('/\{\{\s*\$([a-z0-9_]+)\s*\}\}/i', function (array $matches) use ($variables): string {
            return $variables[$matches[1]] ?? '';
        }, $template);
    }

    private function renderHeader(EmployeeDocumentTemplate $template): string
    {
        $layout = $template->layout_options ?? [];

        $company = $layout['header_company_name'] ?? config('app.name');
        $contact = $layout['header_contact'] ?? '';
        $address = $layout['header_address'] ?? '';
        $tagline = $layout['header_tagline'] ?? '';

        return implode('<br>', array_filter([$company, $contact, $address, $tagline]));
    }
}
