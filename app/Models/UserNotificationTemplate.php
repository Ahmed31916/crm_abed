<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationTemplate extends Model
{
    protected $fillable = [
        'template_id',
        'user_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function notificationTemplate(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    /**
     * Get user notification template settings
     *
     * @param int $userId
     * @return array
     */
    public static function getUserNotificationTemplateSettings($userId)
    {
        return self::where('user_id', $userId)
            ->with('notificationTemplate')
            ->get()
            ->pluck('is_active', 'notificationTemplate.name')
            ->toArray();
    }
}