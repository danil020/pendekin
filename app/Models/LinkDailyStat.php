<?php

namespace App\Models;

use Database\Factories\LinkDailyStatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkDailyStat extends Model
{
    /** @use HasFactory<LinkDailyStatFactory> */
    use HasFactory;

    protected $fillable = [
        'link_id',
        'date',
        'clicks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clicks' => 'integer',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
