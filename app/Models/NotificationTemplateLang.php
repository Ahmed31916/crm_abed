<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTemplateLang extends Model
{
    protected $fillable = [
        'parent_id',
        'lang',
        'title',
        'content',
        'created_by',
    ];

    public function notificationTemplate(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'parent_id');
    }
}