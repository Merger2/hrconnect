<?php

use App\Models\Reimbursement;
use Illuminate\Support\Facades\Route;

Route::livewire('/reimbursements', 'admin.reimbursement-manager')->name('admin.reimbursements')->can('viewAdminAny', Reimbursement::class);
Route::livewire('/manage-kasbon', 'admin.finance.cash-advance-manager')->name('admin.manage-kasbon')->middleware('feature.lock:cash_advance,admin.cash_advances.manage,admin.dashboard')->can('manageCashAdvances');
