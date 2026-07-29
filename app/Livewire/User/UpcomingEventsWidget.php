<?php

namespace App\Livewire\User;

use App\Models\Announcement;
use App\Models\Holiday;
use App\Models\User;
/**
 * @deprecated Tidak terdaftar di route / Blade mana pun.
 */
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class UpcomingEventsWidget extends Component
{
    /**
     * @var array{type:string, title:string, subtitle:string|null, body:string|null, tone:string}|null
     */
    public ?array $selectedEvent = null;

    public function showAnnouncement(int $announcementId): void
    {
        $announcement = Announcement::visibleForUser(Auth::id())
            ->whereKey($announcementId)
            ->first();

        if (! $announcement) {
            return;
        }

        $this->selectedEvent = [
            'type' => __('Announcement'),
            'title' => $announcement->title,
            'subtitle' => $announcement->priority === 'high' ? __('Important Policy') : __('Announcement'),
            'body' => trim(strip_tags((string) $announcement->content)),
            'tone' => $announcement->priority === 'high' ? 'warning' : 'info',
        ];
    }

    public function showHoliday(int $holidayId): void
    {
        $holiday = Holiday::query()->find($holidayId);

        if (! $holiday) {
            return;
        }

        $this->selectedEvent = [
            'type' => __('Holiday'),
            'title' => $holiday->name,
            'subtitle' => $holiday->date?->translatedFormat('l, d F Y'),
            'body' => __('National holiday or company calendar reminder.'),
            'tone' => 'danger',
        ];
    }

    public function showBirthday(string $userId): void
    {
        $user = User::query()->find($userId);

        if (! $user || ! $user->birth_date) {
            return;
        }

        $this->selectedEvent = [
            'type' => __('Birthday'),
            'title' => $user->name,
            'subtitle' => Carbon::parse($user->birth_date)->translatedFormat('d F'),
            'body' => __('Team birthday reminder.'),
            'tone' => 'warning',
        ];
    }

    public function closeEvent(): void
    {
        $this->selectedEvent = null;
    }

    public function render()
    {
        // 1. Active Announcements (Priority > Normal)
        $announcements = Announcement::visibleForUser(Auth::id())
            ->take(3)
            ->get();

        // 2. Upcoming Holidays (Next 14 days)
        $today = Carbon::today();
        $twoWeeksLater = $today->copy()->addDays(14);

        $holidays = Holiday::whereBetween('date', [$today->format('Y-m-d'), $twoWeeksLater->format('Y-m-d')])
            ->orderBy('date', 'asc')
            ->get();

        // 3. Upcoming Birthdays (Next 7 days)
        // Logic handles separate month/year issues simply by checking month/day
        $nextWeek = $today->copy()->addDays(7);

        $birthdays = User::take(200)->get()
            ->filter(function ($user) use ($today, $nextWeek) {
                if (! $user->birth_date) {
                    return false;
                }

                $birthday = Carbon::parse($user->birth_date)->year($today->year);
                if ($birthday->isPast() && ! $birthday->isToday()) {
                    $birthday->addYear();
                }

                return $birthday->between($today, $nextWeek);
            })
            ->sortBy(function ($user) use ($today) {
                $birthday = Carbon::parse($user->birth_date)->year($today->year);
                if ($birthday->isPast() && ! $birthday->isToday()) {
                    $birthday->addYear();
                }

                return $birthday->timestamp;
            })
            ->take(5);

        // Determine active tab or state
        $hasEvents = $announcements->isNotEmpty() || $holidays->isNotEmpty() || $birthdays->isNotEmpty();

        return view('livewire.user.upcoming-events-widget', [
            'announcements' => $announcements,
            'holidays' => $holidays,
            'birthdays' => $birthdays,
            'hasEvents' => $hasEvents,
        ]);
    }
}
