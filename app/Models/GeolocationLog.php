<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperGeolocationLog
 */
class GeolocationLog extends Model
{
    use HasFactory;

    protected $table = 'geolocation_logs';

    protected $fillable = ['employee_id', 'latitude', 'longitude', 'accuracy', 'device_info', 'logged_at'];
}
