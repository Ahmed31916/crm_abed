<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallAttendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_id',
        'attendee_type',
        'attendee_id',
    ];

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    public function attendee()
    {
        switch ($this->attendee_type) {
            case 'user':
                return $this->belongsTo(User::class, 'attendee_id');
            case 'contact':
                return $this->belongsTo(Contact::class, 'attendee_id');
            case 'lead':
                return $this->belongsTo(Lead::class, 'attendee_id');
            default:
                return null;
        }
    }
}