<?php

namespace App\Support;

use App\Models\Company;
use App\Models\CustomFormSubmission;
use App\Models\HrChecklistTask;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Models\WorkFromHomeRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CommandCenterService
{
    public function __construct(
        private readonly ManagerInboxService $managerInbox,
        private readonly CommercialWorkspaceService $commercial,
    ) {}

    /**
     * @return array{
     *     companies:int,
     *     pending_approvals:int,
     *     overdue_approvals:int,
     *     pending_wfh:int,
     *     pending_forms:int,
     *     overdue_hr_tasks:int,
     *     overdue_project_tasks:int,
     *     active_projects:int,
     *     open_pipeline:float,
     *     weighted_pipeline:float,
     *     overdue_follow_ups:int,
     *     outstanding_invoices:int,
     *     outstanding_invoice_total:float,
     *     overdue_invoices:int,
     *     low_stock_products:int
     * }
     */
    public function summary(User $actor): array
    {
        $companyIds = $this->companyIds($actor);
        $sales = $companyIds === [] ? [
            'open_value' => 0.0,
            'weighted_value' => 0.0,
            'overdue_follow_ups' => 0,
        ] : $this->commercial->salesSummaryForCompanies($companyIds);

        return [
            'companies' => count($companyIds),
            'pending_approvals' => $this->managerInbox->getSummary($actor)['pending'],
            'overdue_approvals' => $this->managerInbox->getSummary($actor)['overdue'],
            'pending_wfh' => $this->pendingWfh($companyIds),
            'pending_forms' => $this->pendingForms($companyIds),
            'overdue_hr_tasks' => $this->overdueHrTasks($actor),
            'overdue_project_tasks' => $this->overdueProjectTasks($companyIds),
            'active_projects' => $this->activeProjects($companyIds),
            'open_pipeline' => (float) ($sales['open_value'] ?? 0),
            'weighted_pipeline' => (float) ($sales['weighted_value'] ?? 0),
            'overdue_follow_ups' => (int) ($sales['overdue_follow_ups'] ?? 0),
            'outstanding_invoices' => $this->outstandingInvoiceCount($companyIds),
            'outstanding_invoice_total' => $this->outstandingInvoiceTotal($companyIds),
            'overdue_invoices' => $this->overdueInvoices($companyIds),
            'low_stock_products' => $this->lowStockProducts($companyIds),
        ];
    }

    /**
     * @return Collection<int, array{label:string,value:string|int|float,tone:string,href:string|null,description:string}>
     */
    public function cards(User $actor): Collection
    {
        $summary = $this->summary($actor);

        return collect([
            [
                'label' => __('Pending Approvals'),
                'value' => $summary['pending_approvals'],
                'tone' => 'warning',
                'href' => route('admin.inbox', absolute: false),
                'description' => __('Leave, overtime, reimbursement, WFH, forms, and task queues.'),
            ],
            [
                'label' => __('Overdue Work'),
                'value' => $summary['overdue_approvals'] + $summary['overdue_hr_tasks'] + $summary['overdue_project_tasks'],
                'tone' => 'danger',
                'href' => route('admin.inbox', ['statusFilter' => 'overdue'], false),
                'description' => __('Approvals, HR checklist tasks, and operational tasks past target.'),
            ],
            [
                'label' => __('Open Pipeline'),
                'value' => $summary['open_pipeline'],
                'tone' => 'primary',
                'href' => route('admin.commercial', absolute: false),
                'description' => __('Active lead, qualified, and proposal opportunities.'),
            ],
            [
                'label' => __('Invoice Exposure'),
                'value' => $summary['outstanding_invoice_total'],
                'tone' => 'info',
                'href' => route('admin.commercial', absolute: false),
                'description' => __('Sent or draft invoices that are not marked paid.'),
            ],
        ]);
    }

    /**
     * @return Collection<int, array{title:string,count:int,tone:string,href:string,description:string}>
     */
    public function actionQueues(User $actor): Collection
    {
        $summary = $this->summary($actor);

        return collect([
            [
                'title' => __('WFH Requests'),
                'count' => $summary['pending_wfh'],
                'tone' => 'cyan',
                'href' => route('admin.inbox', ['activeTab' => 'wfh_requests'], false),
                'description' => __('Remote-work requests waiting for manager or admin review.'),
            ],
            [
                'title' => __('Custom Form Reviews'),
                'count' => $summary['pending_forms'],
                'tone' => 'teal',
                'href' => route('admin.inbox', ['activeTab' => 'custom_forms'], false),
                'description' => __('Submitted field reports, surveys, and internal forms.'),
            ],
            [
                'title' => __('Project Tasks Overdue'),
                'count' => $summary['overdue_project_tasks'],
                'tone' => 'rose',
                'href' => route('admin.operations', absolute: false),
                'description' => __('Operational tasks that need follow-up from the project team.'),
            ],
            [
                'title' => __('Sales Follow-ups Overdue'),
                'count' => $summary['overdue_follow_ups'],
                'tone' => 'amber',
                'href' => route('admin.commercial', absolute: false),
                'description' => __('Opportunities with follow-up date already past.'),
            ],
            [
                'title' => __('Invoices Overdue'),
                'count' => $summary['overdue_invoices'],
                'tone' => 'slate',
                'href' => route('admin.commercial', absolute: false),
                'description' => __('Invoices past due date and not yet paid.'),
            ],
            [
                'title' => __('Low Stock Products'),
                'count' => $summary['low_stock_products'],
                'tone' => 'orange',
                'href' => route('admin.commercial', ['activeTab' => 'stock'], false),
                'description' => __('Stock-tracked products at or below their reorder point.'),
            ],
        ]);
    }

    /**
     * @return list<int>
     */
    public function companyIds(User $actor): array
    {
        if (! $actor->isSuperadmin && $actor->company_id !== null) {
            return [(int) $actor->company_id];
        }

        return Company::query()
            ->orderBy('name')
            ->pluck('id')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();
    }

    /**
     * @param  list<int>  $companyIds
     */
    private function pendingWfh(array $companyIds): int
    {
        return WorkFromHomeRequest::query()
            ->whereIn('company_id', $companyIds)
            ->where('status', WorkFromHomeRequest::STATUS_PENDING)
            ->count();
    }

    /**
     * @param  list<int>  $companyIds
     */
    private function pendingForms(array $companyIds): int
    {
        return CustomFormSubmission::query()
            ->whereIn('company_id', $companyIds)
            ->where('status', CustomFormSubmission::STATUS_SUBMITTED)
            ->count();
    }

    private function overdueHrTasks(User $actor): int
    {
        return HrChecklistTask::query()
            ->whereHas('case.user', fn (Builder $query) => $query->managedBy($actor))
            ->reminderReady()
            ->count();
    }

    /**
     * @param  list<int>  $companyIds
     */
    private function overdueProjectTasks(array $companyIds): int
    {
        return ProjectTask::query()
            ->whereIn('company_id', $companyIds)
            ->whereIn('status', [ProjectTask::STATUS_TODO, ProjectTask::STATUS_IN_PROGRESS])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();
    }

    /**
     * @param  list<int>  $companyIds
     */
    private function activeProjects(array $companyIds): int
    {
        return Project::query()
            ->whereIn('company_id', $companyIds)
            ->where('status', Project::STATUS_ACTIVE)
            ->count();
    }

    /**
     * @param  list<int>  $companyIds
     */
    private function outstandingInvoiceCount(array $companyIds): int
    {
        if (! class_exists(Invoice::class)) {
            return 0;
        }

        return Invoice::query()
            ->whereIn('company_id', $companyIds)
            ->where('status', '!=', Invoice::STATUS_PAID)
            ->count();
    }

    /**
     * @param  list<int>  $companyIds
     */
    private function outstandingInvoiceTotal(array $companyIds): float
    {
        if (! class_exists(Invoice::class)) {
            return 0.0;
        }

        return (float) Invoice::query()
            ->whereIn('company_id', $companyIds)
            ->where('status', '!=', Invoice::STATUS_PAID)
            ->sum('grand_total');
    }

    /**
     * @param  list<int>  $companyIds
     */
    private function overdueInvoices(array $companyIds): int
    {
        if (! class_exists(Invoice::class)) {
            return 0;
        }

        return Invoice::query()
            ->whereIn('company_id', $companyIds)
            ->where('status', '!=', Invoice::STATUS_PAID)
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<', now()->toDateString())
            ->count();
    }

    /**
     * @param  list<int>  $companyIds
     */
    private function lowStockProducts(array $companyIds): int
    {
        if (! class_exists(Product::class)) {
            return 0;
        }

        return Product::query()
            ->with('stockMovements')
            ->whereIn('company_id', $companyIds)
            ->where('status', Product::STATUS_ACTIVE)
            ->where('stock_tracking', true)
            ->where('reorder_point', '>', 0)
            ->get()
            ->filter(fn (Product $product): bool => $product->isLowStock())
            ->count();
    }
}
