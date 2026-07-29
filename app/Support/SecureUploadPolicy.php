<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class SecureUploadPolicy
{
    /**
     * @return array<int, mixed>
     */
    public function rules(string $category): array
    {
        $profile = $this->profile($category);

        return [
            'file',
            File::types($profile['extensions'])->max($profile['max_kb']),
            function (string $attribute, mixed $value, \Closure $fail) use ($profile): void {
                if (! $value instanceof UploadedFile) {
                    return;
                }

                if (! $this->hasAllowedExtension($value, $profile['extensions'])) {
                    $fail(__('The uploaded file type is not allowed.'));

                    return;
                }

                if ($this->hasDangerousDoubleExtension($value)) {
                    $fail(__('The uploaded filename is not allowed.'));

                    return;
                }

                if (! in_array((string) $value->getMimeType(), $profile['mimes'], true)) {
                    $fail(__('The uploaded file MIME type is not allowed.'));
                }
            },
        ];
    }

    public function randomFilename(UploadedFile $file, string $prefix = ''): string
    {
        $extension = Str::lower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $prefix = trim(Str::slug($prefix), '-');

        return trim($prefix.'-'.Str::uuid()->toString(), '-').'.'.$extension;
    }

    /**
     * @return array{extensions:array<int,string>,mimes:array<int,string>,max_kb:int}
     */
    private function profile(string $category): array
    {
        return match ($category) {
            'spreadsheet' => [
                'extensions' => ['csv', 'xls', 'xlsx', 'ods'],
                'mimes' => [
                    'text/plain',
                    'text/csv',
                    'application/csv',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.oasis.opendocument.spreadsheet',
                ],
                'max_kb' => 10 * 1024,
            ],
            'image' => [
                'extensions' => ['jpg', 'jpeg', 'png'],
                'mimes' => ['image/jpeg', 'image/png'],
                'max_kb' => 5 * 1024,
            ],
            default => [
                'extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
                'mimes' => ['image/jpeg', 'image/png', 'application/pdf'],
                'max_kb' => 10 * 1024,
            ],
        };
    }

    /**
     * @param  array<int, string>  $extensions
     */
    private function hasAllowedExtension(UploadedFile $file, array $extensions): bool
    {
        return in_array(Str::lower($file->getClientOriginalExtension()), $extensions, true);
    }

    private function hasDangerousDoubleExtension(UploadedFile $file): bool
    {
        $name = Str::lower($file->getClientOriginalName());
        $parts = array_values(array_filter(explode('.', $name)));

        if (count($parts) < 3) {
            return false;
        }

        $dangerous = ['php', 'phtml', 'phar', 'js', 'html', 'htm', 'svg', 'exe', 'sh', 'bat', 'cmd'];
        array_pop($parts);

        return collect($parts)->contains(fn (string $part): bool => in_array($part, $dangerous, true));
    }
}
