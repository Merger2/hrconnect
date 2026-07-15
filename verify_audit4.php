<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Requests\Api\ListAttendanceRequest;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$employee = Employee::with('user')->first();
if (! $employee) {
    echo "NO_EMPLOYEE\n";
    exit(1);
}

// Build a request bound to this employee's user
$user = $employee->user;
$request = Request::create('/api/v1/attendance/today', 'GET');
$request->setUserResolver(fn () => $user);

$controller = new AttendanceController(app(AttendanceService::class));

// --- today() shape ---
$resp = $controller->today($request);
$payload = $resp->getData(true);

$ok = true;
function check(&$ok, string $label, bool $cond): void
{
    echo ($cond ? 'PASS' : 'FAIL')."  $label\n";
    if (! $cond) {
        $ok = false;
    }
}

check($ok, 'today: wrapper status=success', ($payload['status'] ?? null) === 'success');
check($ok, 'today: data.has_clocked_in present', array_key_exists('has_clocked_in', $payload['data'] ?? []));
check($ok, 'today: data.has_clocked_out present', array_key_exists('has_clocked_out', $payload['data'] ?? []));
check($ok, 'today: data.attendance present', array_key_exists('attendance', $payload['data'] ?? []));

// --- statusForFe mapping via reflection across real rows ---
$method = new ReflectionMethod($controller, 'statusForFe');
$valid = ['present', 'late', 'absent', 'wfa'];
$allValid = true;
$seen = [];
foreach (Attendance::limit(50)->get() as $a) {
    $s = $method->invoke($controller, $a);
    $seen[$s] = ($seen[$s] ?? 0) + 1;
    if (! in_array($s, $valid, true)) {
        $allValid = false;
        echo "  BAD STATUS '$s' for attendance {$a->id}\n";
    }
}
check($ok, 'all returned status values in FE set {present,late,absent,wfa}', $allValid);
echo '  status distribution: '.json_encode($seen)."\n";

// --- index() shape ---
$indexReq = ListAttendanceRequest::create('/api/v1/attendance?period='.now()->format('Y-m').'&per_page=50');
$indexReq->setUserResolver(fn () => $user);
// FormRequest needs to be validated/resolved; emulate by calling rules manually
$indexReq->validateResolved();
$iresp = $controller->index($indexReq);
$idata = $iresp->getData(true);

check($ok, 'index: wrapper status=success', ($idata['status'] ?? null) === 'success');
check($ok, 'index: data is array', is_array($idata['data'] ?? null));
check($ok, 'index: meta.current_page present', isset($idata['meta']['current_page']));
if (! empty($idata['data'])) {
    $first = $idata['data'][0];
    check($ok, 'index: record has status', array_key_exists('status', $first));
    check($ok, 'index: record has clock_in', array_key_exists('clock_in', $first));
    check($ok, 'index: record has clock_out', array_key_exists('clock_out', $first));
    check($ok, 'index: record status in FE set', in_array($first['status'], $valid, true));
}

echo $ok ? "\n=== ALL CHECKS PASSED ===\n" : "\n=== FAILURES PRESENT ===\n";
exit($ok ? 0 : 1);
