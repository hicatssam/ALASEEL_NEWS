<?php

namespace App\Models;

use App\Models\Concerns\HasAiTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveBroadcast extends Model
{
    use HasAiTranslations, SoftDeletes;

    protected $with = ['contentTranslations'];

    protected $fillable = [
        'user_id','title','description','platform','stream_url',
        'embed_code','status','started_at','ended_at'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function translatableFields(): array
    {
        return ['title', 'description'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }
}
