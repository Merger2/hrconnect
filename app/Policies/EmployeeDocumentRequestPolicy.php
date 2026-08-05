<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EmployeeDocumentRequest;
use App\Models\User;
use App\Support\MultiCompanyService;

final class EmployeeDocumentRequestPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin ? true : null;
    }

    public function __construct(
        private readonly MultiCompanyService $multiCompany,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->isUser;
    }

    public function viewAdminAny(User $user): bool
    {
        return $user->can('viewAdminDocumentRequests');
    }

    public function view(User $user, EmployeeDocumentRequest $request): bool
    {
        if (! $this->sameCompany($user, $request)) {
            return false;
        }

        return $user->can('viewAdminDocumentRequests') || $request->employee?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isUser;
    }

    public function createForEmployee(User $user): bool
    {
        return $user->allowsAdminPermission([
            'admin.document_requests.request',
            'admin.document_requests.fulfill',
        ]);
    }

    public function upload(User $user, EmployeeDocumentRequest $request): bool
    {
        if (! $this->sameCompany($user, $request)) {
            return false;
        }

        return $request->employee?->user_id === $user->id
            && in_array($request->status, [
                EmployeeDocumentRequest::STATUS_REQUESTED,
                EmployeeDocumentRequest::STATUS_REJECTED,
                EmployeeDocumentRequest::STATUS_UPLOADED,
            ], true);
    }

    public function fulfill(User $user, EmployeeDocumentRequest $request): bool
    {
        if (! $this->sameCompany($user, $request)) {
            return false;
        }

        return $user->allowsAdminPermission('admin.document_requests.fulfill')
            && in_array($request->status, [
                EmployeeDocumentRequest::STATUS_PENDING,
                EmployeeDocumentRequest::STATUS_UPLOADED,
                EmployeeDocumentRequest::STATUS_GENERATED,
            ], true);
    }

    public function generate(User $user, EmployeeDocumentRequest $request): bool
    {
        if (! $this->sameCompany($user, $request)) {
            return false;
        }

        return $user->allowsAdminPermission([
            'admin.document_requests.generate',
            'admin.document_requests.fulfill',
        ])
            && in_array($request->status, [
                EmployeeDocumentRequest::STATUS_PENDING,
                EmployeeDocumentRequest::STATUS_REQUESTED,
                EmployeeDocumentRequest::STATUS_UPLOADED,
            ], true);
    }

    public function reject(User $user, EmployeeDocumentRequest $request): bool
    {
        if (! $this->sameCompany($user, $request)) {
            return false;
        }

        return $user->allowsAdminPermission('admin.document_requests.fulfill')
            && ! in_array($request->status, [
                EmployeeDocumentRequest::STATUS_READY,
                EmployeeDocumentRequest::STATUS_EXPIRED,
            ], true);
    }

    public function download(User $user, EmployeeDocumentRequest $request): bool
    {
        if (! $this->sameCompany($user, $request)) {
            return false;
        }

        return $request->generated_path !== null
            && ($request->employee?->user_id === $user->id || $user->can('viewAdminDocumentRequests'));
    }

    public function downloadUpload(User $user, EmployeeDocumentRequest $request): bool
    {
        if (! $this->sameCompany($user, $request)) {
            return false;
        }

        return $request->uploaded_path !== null
            && ($request->employee?->user_id === $user->id || $user->can('viewAdminDocumentRequests'));
    }

    protected function sameCompany(User $actor, EmployeeDocumentRequest $request): bool
    {
        // M21 AUDIT: dulu custom employeeHasAccess() (list role hardcoded:
        // super-admin/admin/manager + company match) — inkonsisten dengan 9+
        // policy lain yang memakai MultiCompanyService::canAccessUser().
        // canAccessUser menutup celah: manager/admin lintas company tidak lagi
        // otomatis dapat akses (hanya superadmin + same-company).
        $request->loadMissing('employee.user');

        return $request->employee?->user !== null
            && $this->multiCompany->canAccessUser($actor, $request->employee->user);
    }
}
