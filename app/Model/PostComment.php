<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostComment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id', 'post_id', 'message',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
    ];

    /*
     * Relationships
     */

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return HasMany<Reaction, $this>
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    /**
     * @return HasMany<HashtagLink, $this>
     */
    public function hashtagLinks(): HasMany
    {
        return $this->hasMany(HashtagLink::class, 'post_comment_id', 'id');
    }

    /**
     * @return BelongsToMany<Hashtag, $this>
     */
    public function hashtags(): BelongsToMany
    {
        return $this->belongsToMany(
            Hashtag::class,
            'hashtag_links',
            'post_comment_id',
            'hashtag_id'
        )->withTimestamps();
    }

    // mentions rows
    /**
     * @return HasMany<Mention, $this>
     */
    public function mentions(): HasMany
    {
        return $this->hasMany(Mention::class, 'post_comment_id', 'id');
    }
}
