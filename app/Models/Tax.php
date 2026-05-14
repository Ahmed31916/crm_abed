<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tax extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rate',
        'type',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }




}