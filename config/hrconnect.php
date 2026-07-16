<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HRConnect Application Configuration
    |--------------------------------------------------------------------------
    |
    | Central configuration for HRConnect-specific features.
    |
    */

    /*
     * Face Recognition: maximum cosine distance threshold (0.0 - 1.0).
     * 0.15 ≈ 85% similarity. Lower = stricter matching.
     * Can be overridden via CompanySetting key 'face_distance_threshold'.
     */
    'face_distance_threshold' => (float) env('FACE_DISTANCE_THRESHOLD', 0.4),

    /*
     * Payroll: monthly working hours for hourly rate calculation.
     * Standard Indonesia: 173 hours/month (40 hours/week × 4.33 weeks).
     */
    'monthly_working_hours' => (int) env('MONTHLY_WORKING_HOURS', 173),

    /*
     * Leave cash out daily rate divisor.
     * Per PP 35/2021 Pasal 40 Ayat 4 jo. KEP-102/MEN/VI/2004:
     * - 21 for 5-day work week (5 × 4.33 ≈ 21.67 → rounded down)
     * - 25 for 6-day work week (6 × 4.33 ≈ 25.98 → rounded down)
     * Pattern from Quanta HRIS: fixed divisor, not dynamic countWorkingDays().
     */
    'leave_cash_out_daily_divisor' => (int) env('LEAVE_CASH_OUT_DAILY_DIVISOR', 21),

    /*
     * Attendance: default penalty amount per late/alpha occurrence (IDR).
     */
    'attendance_penalty_per_day' => (int) env('ATTENDANCE_PENALTY_PER_DAY', 50000),

    /*
     * Password expiry default (days). Can be overridden via CompanySetting.
     */
    'password_expiry_days' => (int) env('PASSWORD_EXPIRY_DAYS', 90),

    /*
     * PTKP defaults (Penghasilan Tidak Kena Pajak).
     * Overridable via CompanySetting keys ptkp_*.
     */
    'ptkp' => [
        'base_single' => (float) env('PTKP_BASE_SINGLE', 54_000_000),
        'base_married' => (float) env('PTKP_BASE_MARRIED', 58_500_000),
        'per_dependent' => (float) env('PTKP_PER_DEPENDENT', 4_500_000),
        'max_dependents' => (int) env('PTKP_MAX_DEPENDENTS', 3),
    ],
];
