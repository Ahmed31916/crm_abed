<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'name',
        'type',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    public function notificationTemplateLangs(): HasMany
    {
        return $this->hasMany(NotificationTemplateLang::class, 'parent_id');
    }

    public function userNotificationTemplates(): HasMany
    {
        return $this->hasMany(UserNotificationTemplate::class, 'template_id');
    }

    public function getContentForCompany($companyId = null)
    {
        $companyId = $companyId ?? createdBy();
        return $this->notificationTemplateLangs()->where('created_by', $companyId);
    }

    /**
     * Get templates by type
     */
    public static function getByType($type)
    {
        return self::where('type', $type)->get();
    }

    /**
     * Get available notification types
     */
    public static function getAvailableTypes()
    {
        return self::distinct()->pluck('type')->toArray();
    }
}