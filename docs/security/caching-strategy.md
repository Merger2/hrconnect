# HRConnect - Caching Strategy

> **Dokumen ini berisi strategi caching untuk HRConnect.**
> Gunakan caching untuk meningkatkan performa dan mengurangi beban database.

---

## 1. CACHE DRIVER

```php
// config/cache.php
'default' => env('CACHE_DRIVER', 'redis'), // Preferred: Redis
// Fallback: 'database' jika Redis tidak tersedia
```

**Rekomendasi:**
- Development: `database` (simple, no extra setup)
- Production: `redis` (faster, supports tags)

---

## 2. CACHE STRATEGY BY MODULE

### 2.1 Attendance Cache

| Cache Key | Data | TTL | Invalidation |
|-----------|------|-----|-------------|
| `attendance:today:{employee_id}` | Today's attendance record | 1 day | On clock in/out |
| `attendance:monthly:{employee_id}:{YYYY-MM}` | Monthly summary | 1 hour | On new attendance |
| `attendance:dept:today:{dept_id}` | Department attendance today | 5 minutes | On clock in/out |
| `attendance:office:current` | Current office attendance count | 1 minute | On clock in/out |

```php
// Example usage
Cache::remember(
    "attendance:monthly:{$employeeId}:{$period}",
    now()->addHour(),
    fn() => $this->calculateMonthlySummary($employeeId, $period)
);
```

### 2.2 Leave Cache

| Cache Key | Data | TTL | Invalidation |
|-----------|------|-----|-------------|
| `leave:quota:{employee_id}:{year}` | Employee leave quota | 1 day | On quota change |
| `leave:pending:count` | Pending leave requests count | 5 minutes | On new request/approval |
| `leave:calendar:{YYYY-MM}` | Leave calendar data | 10 minutes | On approval/rejection |

```php
// Invalidate on leave approval
Cache::forget("leave:quota:{$employeeId}:{$year}");
Cache::forget("leave:pending:count");
```

### 2.3 Payroll Cache

| Cache Key | Data | TTL | Invalidation |
|-----------|------|-----|-------------|
| `payroll:slip:{payroll_id}` | Payroll slip data | 1 hour | On payroll update |
| `payroll:summary:{period}` | Payroll summary by period | 30 minutes | On payroll generate |
| `payroll:dept:summary:{period}:{dept_id}` | Department payroll summary | 30 minutes | On payroll update |

```php
// Payroll is sensitive, shorter TTL
Cache::remember(
    "payroll:summary:{$period}",
    now()->addMinutes(30),
    fn() => $this->calculatePayrollSummary($period)
);
```

### 2.4 Employee Cache

| Cache Key | Data | TTL | Invalidation |
|-----------|------|-----|-------------|
| `employee:count` | Total employee count | 10 minutes | On create/delete |
| `employee:dept:{dept_id}` | Employees in department | 5 minutes | On employee change |
| `employee:org-chart` | Full organizational chart | 1 hour | On department/position change |
| `employee:search:{query}` | Search results | 5 minutes | On employee change |

### 2.5 Company Settings Cache

| Cache Key | Data | TTL | Invalidation |
|-----------|------|-----|-------------|
| `settings:company` | Company profile | 1 day | On settings update |
| `settings:attendance` | Attendance rules | 1 day | On settings update |
| `settings:geofence:{branch_id}` | Branch geofence config | 1 day | On settings update |
| `settings:all` | All settings combined | 1 day | On any settings update |

```php
// Settings change infrequently, long TTL
Cache::remember('settings:attendance', now()->addDay(), function () {
    return CompanySetting::where('type', 'attendance')->get()->pluck('value', 'key');
});
```

### 2.6 Holiday & Shift Cache

| Cache Key | Data | TTL | Invalidation |
|-----------|------|-----|-------------|
| `holidays:{year}` | Holiday list for year | 1 month | On holiday CRUD |
| `shifts:active` | Active shifts | 1 day | On shift change |
| `shift:schedule:{employee_id}:{YYYY-MM}` | Employee shift schedule | 1 day | On schedule change |

---

## 3. CACHE TAGS (Redis Only)

```php
// Group related caches with tags
Cache::tags(['attendance', 'employee:123'])->put('key', $value, $ttl);

// Invalidate by tag
Cache::tags(['attendance'])->flush(); // Clear all attendance cache
Cache::tags(['employee:123'])->flush(); // Clear all cache for employee 123
```

**Tag Groups:**
| Tag | Invalidate When |
|-----|-----------------|
| `attendance` | Any attendance change |
| `employee:{id}` | Employee data change |
| `leave` | Any leave change |
| `payroll:{period}` | Payroll change for period |
| `settings` | Any settings change |
| `holidays` | Holiday CRUD |
| `shifts` | Shift/schedule change |

---

## 4. CACHE INVALIDATION PATTERNS

### 4.1 Model Observer Pattern
```php
// app/Observers/AttendanceObserver.php
class AttendanceObserver
{
    public function created(Attendance $attendance): void
    {
        // Invalidate related caches
        Cache::forget("attendance:today:{$attendance->employee_id}");
        Cache::forget("attendance:monthly:{$attendance->employee_id}:{$attendance->date->format('Y-m')}");
        Cache::tags(['attendance'])->flush();
    }

    public function updated(Attendance $attendance): void
    {
        Cache::forget("attendance:today:{$attendance->employee_id}");
        Cache::tags(['attendance'])->flush();
    }
}
```

### 4.2 Event-Listener Pattern
```php
// Event
class LeaveApproved
{
    public function __construct(public Leave $leave) {}
}

// Listener
class InvalidateLeaveCache
{
    public function handle(LeaveApproved $event): void
    {
        Cache::forget("leave:quota:{$event->leave->employee_id}:{$event->leave->start_date->year}");
        Cache::forget("leave:pending:count");
        Cache::forget("leave:calendar:{$event->leave->start_date->format('Y-m')}");
    }
}
```

---

## 5. QUERY OPTIMIZATION (Not Cache, But Related)

### 5.1 Eager Loading
```php
// BAD: N+1 problem
$employees = Employee::all();
foreach ($employees as $employee) {
    echo $employee->department->name; // N+1
}

// GOOD: Eager load
$employees = Employee::with(['department', 'position', 'branch'])->get();
```

### 5.2 Select Only Needed Columns
```php
// BAD: Select all columns
$employees = Employee::all();

// GOOD: Select only needed
$employees = Employee::select('id', 'full_name', 'employee_number')->get();
```

### 5.3 Database Indexes
```php
// Add indexes for frequently queried columns
$table->index(['employee_id', 'date']); // attendance
$table->index(['status', 'created_at']); // approvals
$table->index(['period', 'employee_id']); // payrolls
```

---

## 6. CACHE WARMING

### 6.1 Scheduled Cache Warming
```php
// app/Console/Commands/WarmCache.php
class WarmCache extends Command
{
    protected $signature = 'cache:warm';

    public function handle(): void
    {
        // Warm company settings
        Cache::remember('settings:company', now()->addDay(), function () {
            return Company::first();
        });

        // Warm holidays for current year
        Cache::remember("holidays:" . now()->year, now()->addMonth(), function () {
            return Holiday::whereYear('date', now()->year)->get();
        });

        // Warm active shifts
        Cache::remember('shifts:active', now()->addDay(), function () {
            return Shift::where('is_active', true)->get();
        });
    }
}
```

### 6.2 Schedule
```php
// routes/console.php
Schedule::command('cache:warm')->dailyAt('00:00');
Schedule::command('cache:clear --tags=settings')->dailyAt('00:05');
```

---

## 7. CACHE MONITORING

### 7.1 Cache Hit Rate
```php
// Track cache hits vs misses
Event::listen(CacheHit::class, function ($event) {
    Log::channel('cache')->info('Cache Hit', ['key' => $event->key]);
});

Event::listen(CacheMissed::class, function ($event) {
    Log::channel('cache')->info('Cache Miss', ['key' => $event->key]);
});
```

### 7.2 Cache Size
```bash
# Redis cache info
redis-cli info memory
redis-cli dbsize

# Laravel cache stats
php artisan cache:table  # If using database driver
```

---

## 8. CACHE BEST PRACTICES

### 8.1 Do's
- ✅ Use `Cache::remember()` for read-heavy data
- ✅ Use tags for grouped invalidation
- ✅ Set appropriate TTL (not too long, not too short)
- ✅ Invalidate cache on data change
- ✅ Cache expensive queries (aggregations, joins)
- ✅ Use cache warming for critical data

### 8.2 Don'ts
- ❌ Don't cache user-specific session data
- ❌ Don't cache real-time data (use WebSockets instead)
- ❌ Don't cache without invalidation strategy
- ❌ Don't cache sensitive data without encryption
- ❌ Don't rely on cache for critical business logic

---

## 9. CACHE CONFIGURATION

```php
// config/cache.php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
    'database' => [
        'driver' => 'database',
        'table' => 'cache',
        'connection' => null,
        'lock_connection' => null,
    ],
],
```

```php
// config/database.php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
    ],
    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_CACHE_DB', 1),
    ],
],
```

---

*Dokumen ini harus diikuti untuk performa optimal.*
*Terakhir diupdate: 2026-05-08*
