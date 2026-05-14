<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Call extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'parent_module',
        'parent_id',
        'status',
        'created_by',
        'assigned_to',
        'google_calendar_event_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(CallAttendee::class);
    }

    public function parent()
    {
        switch ($this->parent_module) {
            case 'lead':
                return $this->belongsTo(Lead::class, 'parent_id');
            case 'account':
                return $this->belongsTo(Account::class, 'parent_id');
            case 'contact':
                return $this->belongsTo(Contact::class, 'parent_id');
            case 'opportunity':
                return $this->belongsTo(Opportunity::class, 'parent_id');
            case 'case':
                return $this->belongsTo(CaseModel::class, 'parent_id');
            case 'project':
                return $this->belongsTo(Project::class, 'parent_id');
            default:
                return null;
        }
    }
}