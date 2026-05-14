<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Document extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'account_id',
        'folder_id',
        'type_id',
        'opportunity_id',
        'status',
        'publish_date',
        'expiration_date',
        'attachment',
        'description',
        'created_by',
        'assigned_to',
    ];

    protected $casts = [
        'publish_date' => 'date',
        'expiration_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'attachment_url',
        'attachment_name',
        'attachment_size'
    ];

    /**
     * Get the account that owns this document.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the folder that contains this document.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class, 'folder_id');
    }

    /**
     * Get the type of this document.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'type_id');
    }

    /**
     * Get the opportunity related to this document.
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'opportunity_id');
    }

    /**
     * Get the user who created this document.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user assigned to this document.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->singleFile();
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->performOnCollections('attachments')
            ->nonQueued();
    }

    public function getAttachmentUrlAttribute()
    {
        $media = $this->getFirstMedia('attachments');
        return $media ? $media->getUrl() : null;
    }

    public function getAttachmentNameAttribute()
    {
        $media = $this->getFirstMedia('attachments');
        return $media ? $media->name : null;
    }

    public function getAttachmentSizeAttribute()
    {
        $media = $this->getFirstMedia('attachments');
        return $media ? $media->size : null;
    }
}