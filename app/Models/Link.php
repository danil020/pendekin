<?php

namespace App\Models;

use Database\Factories\LinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Link extends Model
{
    /** @use HasFactory<LinkFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'original_url',
        'is_alias',
        'is_active',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_alias' => 'boolean',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(LinkDailyStat::class);
    }
}
