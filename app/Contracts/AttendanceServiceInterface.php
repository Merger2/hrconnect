<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Attendance Service Interface (Community Edition)
 * Mirrors PasPapan CommunityService contract for thesis build.
 */
interface AttendanceServiceInterface
{
    /**
     * Store the attachment file.
     */
    public function storeAttachment(UploadedFile $file): string;

    /**
     * Get the URL for the attachment.
     */
    public function getAttachmentUrl(Attendance $attendance): string|array|null;

    /**
     * Check if Face Enrollment should be enforced.
     */
    public function shouldEnforceFaceEnrollment(): bool;

    /**
     * Store attendance photo securely.
     */
    public function storeAttendancePhoto(string $base64Data, string $filename): string;

    /**
     * Register a face descriptor for the user.
     */
    public function registerFace(User $user, array $descriptor): void;

    /**
     * Remove the user's face registration.
     */
    public function removeFace(User $user): void;
}
