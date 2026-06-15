<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentRequest extends Model
{
    public const PENDING_STATUS = 'pending';
    public const APPROVED_STATUS = 'approved';
    public const REJECTED_STATUS = 'rejected';
    public const DEPOSIT_TYPE = 'deposit';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id', 'transaction_id', 'status', 'type', 'reason', 'message', 'amount',
    ];

    protected $appends = ['files'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [];

    /**
     * Get request files path as array.
     */
    public function getFilesAttribute() {
        $files = [];
        foreach ($this->attachments as $attachment){
            $files[] = $attachment['filename'];
        }
        return $files;
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        // belongsTo(Related::class, foreignKey_on_this_model, ownerKey_on_related)
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }
}
