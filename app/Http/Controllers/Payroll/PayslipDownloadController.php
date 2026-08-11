<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Services\Payroll\PayslipPdfService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Download payslip via route web (pengganti dispatch 'download-file' yang
 * tidak punya listener — P1 fix 2026-08-11).
 *
 * Keputusan Fikih 2026-08-11: "Minta PIN saat download (paling aman)".
 * - Pemilik payslip (employee) harus memasukkan PIN payslip-nya saat download;
 *   PDF di-enkripsi dengan PIN plaintext yang diketik — bukan hash (fix akar
 *   masalah: sebelumnya PDF di-enkripsi dgn hash argon2id sehingga TIDAK bisa
 *   dibuka karyawan dengan PIN-nya).
 * - Staff/admin terotorisasi (Policy::download) langsung dapat file tanpa PIN
 *   prompt — mereka sudah punya akses penuh ke gaji via UI admin.
 *
 * Ability 'download' (PayrollPolicy::download) memastikan pemilik payroll /
 * admin + status approved/paid — menutup IDOR yang ada di jalur Livewire lama
 * (authorize('downloadPayslip') = cek permission saja).
 */
class PayslipDownloadController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request, Payroll $payroll, PayslipPdfService $service): View|RedirectResponse|BinaryFileResponse
    {
        $this->authorize('download', $payroll);

        $employee = $payroll->employee;
        $isOwner = $employee && $employee->user_id === $request->user()->id;

        if ($isOwner) {
            if (! $employee->hasValidPayslipPassword()) {
                return redirect()->route('my-payslips')
                    ->with('error', __('Set a payslip password first to download payslips.'));
            }

            return view('payroll.payslip-pin', ['payroll' => $payroll]);
        }

        // Staff/admin terotorisasi: stream langsung (PDF tanpa enkripsi —
        // mereka sudah punya akses gaji di UI admin).
        return $this->stream($payroll, $service, null);
    }

    public function store(Request $request, Payroll $payroll, PayslipPdfService $service): RedirectResponse|BinaryFileResponse
    {
        $this->authorize('download', $payroll);

        $employee = $payroll->employee;

        if (! $employee || $employee->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'pin' => ['required', 'string'],
        ]);

        if (! $employee->hasValidPayslipPassword() || ! Hash::check($validated['pin'], $employee->payslip_password)) {
            return back()->withErrors(['pin' => __('The PIN you entered is incorrect.')]);
        }

        // PDF di-enkripsi dengan PIN plaintext (bukan hash) supaya karyawan
        // bisa membukanya dengan PIN yang mereka set.
        return $this->stream($payroll, $service, $validated['pin']);
    }

    protected function stream(Payroll $payroll, PayslipPdfService $service, ?string $password): BinaryFileResponse
    {
        $path = $service->generateAndStore($payroll, $password);

        $filename = sprintf(
            'payslip-%s-%s.pdf',
            $payroll->period,
            $payroll->employee?->employee_number ?? 'unknown'
        );

        return response()->download($path, $filename);
    }
}
