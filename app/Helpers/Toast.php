<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Facades\Session;

/**
 * Toast helper for flashing toast notifications from backend.
 *
 * Usage:
 *   toast()->success('Absen berhasil', 'Kamu absen pada 08:00 WIB');
 *   toast()->error('Gagal absen', 'Kamera tidak dapat diakses');
 *   toast()->warning('Perhatian', 'GPS di luar area, gunakan WFA');
 *   toast()->info('Info', 'Pengajuan cuti perlu approval HRD');
 *
 * With action:
 *   toast()->success('Cuti disetujui', 'Pengajuan cuti 1-3 Januari', [
 *       'label' => 'Lihat Detail',
 *       'url' => route('leave.show', $leave),
 *   ]);
 *
 * Persist (won't auto-dismiss):
 *   toast()->error('Error kritis', 'Hubungi IT', ['persist' => true]);
 */
class Toast
{
    public const TYPE_SUCCESS = 'success';

    public const TYPE_ERROR = 'error';

    public const TYPE_WARNING = 'warning';

    public const TYPE_INFO = 'info';

    protected array $toasts = [];

    protected string $sessionKey = 'toasts';

    public function success(string $title, string $message = '', array $options = []): self
    {
        return $this->add(self::TYPE_SUCCESS, $title, $message, $options);
    }

    public function error(string $title, string $message = '', array $options = []): self
    {
        return $this->add(self::TYPE_ERROR, $title, $message, $options);
    }

    public function warning(string $title, string $message = '', array $options = []): self
    {
        return $this->add(self::TYPE_WARNING, $title, $message, $options);
    }

    public function info(string $title, string $message = '', array $options = []): self
    {
        return $this->add(self::TYPE_INFO, $title, $message, $options);
    }

    public function add(string $type, string $title, string $message = '', array $options = []): self
    {
        $toast = array_merge([
            'id' => 'toast-'.uniqid(),
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'duration' => 5000,
            'action' => null,
            'persist' => false,
        ], $options);

        $this->toasts[] = $toast;

        return $this;
    }

    public function action(string $label, string $url, bool $persist = false): self
    {
        if (! empty($this->toasts)) {
            $last = count($this->toasts) - 1;
            $this->toasts[$last]['action'] = [
                'label' => $label,
                'url' => $url,
                'persist' => $persist,
            ];
        }

        return $this;
    }

    public function persist(bool $persist = true): self
    {
        if (! empty($this->toasts)) {
            $last = count($this->toasts) - 1;
            $this->toasts[$last]['persist'] = $persist;
            $this->toasts[$last]['duration'] = $persist ? 0 : 5000;
        }

        return $this;
    }

    public function duration(int $milliseconds): self
    {
        if (! empty($this->toasts)) {
            $last = count($this->toasts) - 1;
            $this->toasts[$last]['duration'] = $milliseconds;
        }

        return $this;
    }

    /**
     * Flash all toasts to session for next request.
     * Call this in controller before redirect/return.
     */
    public function flash(): self
    {
        if (! empty($this->toasts)) {
            Session::flash($this->sessionKey, $this->toasts);
            $this->toasts = [];
        }

        return $this;
    }

    /**
     * Get toasts from session (for blade component to render).
     */
    public static function getFromSession(): array
    {
        return Session::pull('toasts', []);
    }

    /**
     * Check if there are toasts in session.
     */
    public static function hasFromSession(): bool
    {
        return Session::has('toasts');
    }
}
