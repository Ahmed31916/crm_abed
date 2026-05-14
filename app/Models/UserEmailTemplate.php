<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEmailTemplate extends Model
{
    protected $fillable = [
        'template_id',
        'user_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    /**
     * Get user email template settings
     *
     * @param int $userId
     * @return array
     */
    public static function getUserEmailTemplateSettings($userId)
    {
        return self::where('user_id', $userId)
            ->with('emailTemplate')
            ->get()
            ->pluck('is_active', 'emailTemplate.name')
            ->toArray();
    }
}
