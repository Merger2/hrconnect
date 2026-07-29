<?php

<<<<<<< HEAD
use App\Models\Employee;
=======
use App\Models\Setting;
>>>>>>> main
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

if (! function_exists('generate_employee_number')) {
    function generate_employee_number(?string $prefix = 'EMP'): string
    {
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        $key = "employee_number_counter_{$year}{$month}";

        $counter = Cache::increment($key, 1, now()->addDays(32));

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $counter);
    }
}

if (! function_exists('calculate_distance')) {
    function calculate_distance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

if (! function_exists('app_name')) {
    function app_name(): string
    {
        return config('app.name', 'HRConnect');
    }
}

if (! function_exists('format_currency')) {
    function format_currency(float $amount, ?string $locale = 'id_ID'): string
    {
<<<<<<< HEAD
        return 'Rp' . number_format($amount, 0, ',', '.');
=======
        return 'Rp'.number_format($amount, 0, ',', '.');
>>>>>>> main
    }
}

if (! function_exists('get_attendance_status')) {
    function get_attendance_status(?string $clockIn, string $shiftStart): string
    {
        if (! $clockIn) {
            return 'absent';
        }

        $clockInTime = Carbon::parse($clockIn);
        $shiftStartTime = Carbon::parse($shiftStart);
        $lateMinutes = $clockInTime->diffInMinutes($shiftStartTime, false);

        if ($lateMinutes <= 0) {
            return 'on_time';
        }

        if ($lateMinutes <= config('attendance.grace_period', 15)) {
            return 'on_time';
        }

        return 'late';
    }
}

if (! function_exists('calculate_late_minutes')) {
    function calculate_late_minutes(?string $clockIn, string $shiftStart): int
    {
        if (! $clockIn) {
            return 0;
        }

        $clockInTime = Carbon::parse($clockIn);
        $shiftStartTime = Carbon::parse($shiftStart);
        $lateMinutes = $clockInTime->diffInMinutes($shiftStartTime, false);

        return max(0, (int) $lateMinutes);
    }
}

if (! function_exists('app_environment')) {
    function app_environment(): string
    {
        return app()->environment();
    }
}

if (! function_exists('is_production')) {
    function is_production(): bool
    {
        return app()->environment('production');
    }
}
<<<<<<< HEAD
=======

if (! function_exists('normalize_internal_url')) {
    function normalize_internal_url(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return $url;
        }

        if (! isset($parts['scheme']) && ! isset($parts['host'])) {
            return $url;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $path.$query.$fragment;
    }
}

if (! function_exists('get_non_root_base_url_path')) {
    function get_non_root_base_url_path(): string
    {
        $segments = explode('/', parse_url(config('app.url'), PHP_URL_PATH));

        return count($segments) < 2 ? '' : $segments[1];
    }
}

if (! function_exists('format_time')) {
    function format_time($time): string
    {
        if (! $time) {
            return '-';
        }

        if (is_string($time)) {
            try {
                $time = Carbon::parse($time);
            } catch (Exception $e) {
                return $time;
            }
        }

        $format = Setting::getValue('app.time_format', '24');
        $showSeconds = (bool) Setting::getValue('app.show_seconds', false);

        if ($format == '12') {
            $formatString = $showSeconds ? 'h:i:s A' : 'h:i A';
        } else {
            $formatString = $showSeconds ? 'H:i:s' : 'H:i';
        }

        return $time->format($formatString);
    }
}
>>>>>>> main
