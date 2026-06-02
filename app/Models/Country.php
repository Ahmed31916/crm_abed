<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'phone_code',
        'flag',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public static function getActiveList(): \Illuminate\Support\Collection
    {
        return static::where('status', true)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}