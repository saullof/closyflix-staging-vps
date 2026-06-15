<?php

namespace App\Model;

use App\Providers\AttachmentServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reel extends Model
{
    protected $fillable = [
        'user_id',
        'caption',
        'is_public',
        'overlay',
        'sound_id',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'overlay' => 'array',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'reel_id')->orderBy('created_at');
    }

    /**
     * @return HasOne<Attachment, $this>
     */
    public function video(): HasOne
    {
        return $this->hasOne(Attachment::class, 'reel_id')
            ->whereIn('type', AttachmentServiceProvider::getTypeByExtension('video'))
            ->oldest();
    }

    /**
     * @return HasOne<Attachment, $this>
     */
    public function cover(): HasOne
    {
        return $this->hasOne(Attachment::class, 'reel_id')
            ->whereIn('type', AttachmentServiceProvider::getTypeByExtension('default'))
            ->oldest();
    }

    /**
     * @return HasMany<ReelView, $this>
     */
    public function views(): HasMany
    {
        return $this->hasMany(ReelView::class);
    }

    /**
     * @return HasMany<ReelComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ReelComment::class);
    }

    /**
     * @return HasMany<Reaction, $this>
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class, 'reel_id');
    }

    /**
     * @return HasMany<UserBookmark, $this>
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(UserBookmark::class, 'reel_id');
    }

    /**
     * @return BelongsTo<Sound, $this>
     */
    public function sound(): BelongsTo
    {
        return $this->belongsTo(Sound::class, 'sound_id');
    }
}
