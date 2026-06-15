<?php

namespace App\Model;

use App\Providers\AttachmentServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    public const PUBLIC_DRIVER = 0;
    public const S3_DRIVER = 1;
    public const WAS_DRIVER = 2;
    public const DO_DRIVER = 3;
    public const MINIO_DRIVER = 4;
    public const PUSHR_DRIVER = 5;
    public const R2_DRIVER = 6;

    // Disable auto incrementing as we set the id manually (uuid)
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id', 'post_id', 'filename',
        'type', 'id', 'driver',
        'payment_request_id', 'message_id', 'message_template_id', 'coconut_id', 'story_id', 'reel_id',
        'has_thumbnail', 'has_blurred_preview', 'blurred_filename', 'length',
    ];

    protected $appends = ['attachmentType', 'path', 'thumbnail'];

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
        'id' => 'string',
    ];

    /*
     * Virtual attributes
     */

    public function getAttachmentTypeAttribute()
    {
        return AttachmentServiceProvider::getAttachmentType($this->type);
    }

    public function getPathAttribute()
    {
        return AttachmentServiceProvider::getFilePathByAttachment($this);
    }

    public function getThumbnailAttribute()
    {
        return AttachmentServiceProvider::getThumbnailPathForAttachmentByResolution($this, 150, 150);
    }

    // TODO: Add get blurredPreview
    public function getBlurredPreviewAttribute()
    {
        if(!$this->has_blurred_preview) return null;
        return AttachmentServiceProvider::getBlurredPreviewPathForAttachment($this);
    }

    /*
     * Relationships
     */

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * @return BelongsTo<UserMessage, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(UserMessage::class, 'message_id');
    }

    /**
     * @return BelongsTo<MessageTemplate, $this>
     */
    public function messageTemplate(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }

    /**
     * @return BelongsTo<PaymentRequest, $this>
     */
    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class, 'payment_request_id');
    }

    /**
     * @return BelongsTo<Story, $this>
     */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * @return BelongsTo<Reel, $this>
     */
    public function reel(): BelongsTo
    {
        return $this->belongsTo(Reel::class);
    }

    /**
     * @return BelongsTo<Sound, $this>
     */
    public function sound(): BelongsTo
    {
        return $this->belongsTo(Sound::class, 'sound_id');
    }

    public static function getDriverName($driver): string {
        return match ($driver) {
            self::PUBLIC_DRIVER => "public",
            self::S3_DRIVER => "s3",
            self::WAS_DRIVER => "was",
            self::DO_DRIVER => "do",
            self::MINIO_DRIVER => "minio",
            self::PUSHR_DRIVER => "pushr",
            self::R2_DRIVER => "r2",
            default => 'unknown',
        };
    }
}
