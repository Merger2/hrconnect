<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperAnnouncementUserView
 */
class AnnouncementUserView extends Model
{
    use HasFactory;

    protected $table = 'announcement_user_views';

    protected $fillable = ['announcement_id', 'user_id', 'viewed_at', 'dismissed_at', 'seen_at'];
}
