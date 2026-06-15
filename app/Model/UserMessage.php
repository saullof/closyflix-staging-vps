<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class UserMessage extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['sender_id', 'receiver_id', 'message', 'price', 'replyTo', 'isSeen', 'story_id', 'message_template_id'];

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
        'sender_id' => 'integer',
        'receiver_id' => 'integer',
    ];

    /*
     * Relationships
     */

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public static function initialMessages($senderID, $receiverID)
    {
        return self::whereRaw('receiver_id = ? and sender_id = ? and replyTo = 0 ', [$receiverID, $senderID])
            ->orWhereRaw('receiver_id = ? and sender_id = ? and  replyTo = 0 ', [$senderID, $receiverID])
            ->first();
    }

    /**
     * @return HasMany<UserMessage, $this>
     */
    public function repliesMessages(): HasMany
    {
        return $this->hasMany(self::class, 'replyTo')->orderBy('dateAdded', 'desc');
    }

    /**
     * @return HasMany<UserMessage, $this>
     */
    public function unseenRepliesMessages(): HasMany
    {
        return $this->hasMany(self::class, 'replyTo')->where('isSeen', 0)->where('sender_id', '!=', Auth::id())->orderBy('dateAdded', 'desc');
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'message_id');
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_message_id');
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function messagePurchases(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_message_id', 'id')->where('status', 'approved')->where('type', 'message-unlock');
    }

    /**
     * @return BelongsTo<Story, $this>
     */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class, 'story_id');
    }

    /**
     * @return BelongsTo<MessageTemplate, $this>
     */
    public function messageTemplate(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }
}
