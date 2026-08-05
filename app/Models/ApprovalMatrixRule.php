<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperApprovalMatrixRule
 */
class ApprovalMatrixRule extends Model
{
    use HasFactory;

    public const MODULE_REIMBURSEMENT = 'reimbursement';

    public const MODULE_CASH_ADVANCE = 'cash_advance';

    public const MODULE_LEAVE = 'leave';

    public const MODULE_OVERTIME = 'overtime';

    public const MODULE_ATTENDANCE_CORRECTION = 'attendance_correction';

    public const MODULE_SHIFT_SWAP = 'shift_swap';

    public const MODULE_ASSET = 'asset';

    public const MODULE_DOCUMENT_REQUEST = 'document_request';

    public const MODULE_PAYROLL_SENSITIVE_ACTION = 'payroll_sensitive_action';

    protected $fillable = [
        'module_name',
        'condition_type',
        'condition_value',
        'approval_level',
        'approver_id',
        'approver_role_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'approval_level' => 'integer',
        ];
    }
}
