<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Story extends Model
{
    protected $fillable = [
        'user_id',
        'expires_at',
        'is_highlight',
        'is_public',
        'overlay',
        'mode',
        'text',
        'bg_preset',
        'link_url',
        'link_text',
        'sound_id',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'is_highlight'=> 'boolean',
        'overlay' => 'array',
    ];

    /**
     * Owner of the story (the user whose bubble this appears under).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Attachments that belong to this story (its media items).
     *
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'story_id')
            ->orderBy('created_at'); // oldest → newest
    }

    /**
     * Users who viewed this story.
     *
     * @return HasMany<StoryView, $this>
     */
    public function views(): HasMany
    {
        return $this->hasMany(StoryView::class);
    }

    /**
     * Scope: only not-expired stories (24h style).
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function getLinkTextAttribute($value)
    {
        if (!empty($this->attributes['link_url']) && empty($value)) {
            return __('Learn more');
        }

        return $value;
    }

    /**
     * @return BelongsTo<Sound, $this>
     */
    public function sound(): BelongsTo
    {
        return $this->belongsTo(Sound::class, 'sound_id');
    }
}
