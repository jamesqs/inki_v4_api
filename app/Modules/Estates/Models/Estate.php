<?php

namespace App\Modules\Estates\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Modules\Categories\Models\Category;
use App\Modules\Attributes\Models\Attribute;
use App\Modules\Locations\Models\Location;
use App\Models\User;
//use App\Modules\Estates\Models\EstateAttributeValue;

/**
 * @method static paginate()
 * @method static create(mixed $validated)
 */

class Estate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'price',
        'price_type',
        'currency',
        'listing_type',
        'status',
        'rejection_reason',
        'submitted_at',
        'reviewed_at',
        'published_at',
        'reviewed_by',
        'location_id',
        'district_id',
        'category_id',
        'accepted',
        'sold',
        'published',
        'address',
        'zip',
        'address_data',
        'custom_attributes',
        'photos',
        'views',
        'floor_plan_data'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'custom_attributes' => 'array',
        'address_data' => 'array',
        'photos' => 'array',
        'floor_plan_data' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
        'price' => 'decimal:2',
        'views' => 'integer'
    ];

    /**
     * Append formatted price to API responses
     */
    protected $appends = ['formatted_price'];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function location(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get formatted price with currency
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->price === null) {
            return '0 ' . ($this->currency ?? 'HUF');
        }
        return number_format($this->price, 0, ',', ' ') . ' ' . ($this->currency ?? 'HUF');
    }

    /**
     * Get the user who reviewed this estate
     */
    public function reviewer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope for approved estates (visible to public)
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for published estates (legacy - redirects to approved)
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for draft estates
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for pending review estates
     */
    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending_review');
    }

    /**
     * Scope for rejected estates
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Check if estate can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    /**
     * Check if estate can be submitted for review
     */
    public function canBeSubmitted(): bool
    {
        return $this->status === 'draft' && $this->isComplete();
    }

    /**
     * Check if estate has all required fields for submission
     */
    public function isComplete(): bool
    {
        // Check required fields
        $requiredFields = [
            'name',
            'description',
            'price',
            'listing_type',
            'location_id',
            'category_id',
        ];

        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        // Check if has at least one photo
        if (empty($this->photos) || count($this->photos) === 0) {
            return false;
        }

        // Check if description is long enough
        if (strlen($this->description) < 50) {
            return false;
        }

        return true;
    }

    /**
     * Submit estate for admin review
     */
    public function submitForReview(): bool
    {
        if (!$this->canBeSubmitted()) {
            return false;
        }

        $this->status = 'pending_review';
        $this->submitted_at = now();
        $this->rejection_reason = null;

        return $this->save();
    }

    /**
     * Cancel review submission
     */
    public function cancelReview(): bool
    {
        if ($this->status !== 'pending_review') {
            return false;
        }

        $this->status = 'draft';
        $this->submitted_at = null;

        return $this->save();
    }

    /**
     * Approve estate
     */
    public function approve($reviewerId): bool
    {
        if ($this->status !== 'pending_review') {
            return false;
        }

        $this->status = 'approved';
        $this->reviewed_at = now();
        $this->published_at = now();
        $this->reviewed_by = $reviewerId;
        $this->rejection_reason = null;

        return $this->save();
    }

    /**
     * Reject estate with reason
     */
    public function reject($reviewerId, $reason): bool
    {
        if ($this->status !== 'pending_review') {
            return false;
        }

        $this->status = 'rejected';
        $this->reviewed_at = now();
        $this->reviewed_by = $reviewerId;
        $this->rejection_reason = $reason;

        return $this->save();
    }

    /**
     * Scope for user's estates
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function attributeValues(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EstateAttributeValue::class);
    }

    // Helper method to get custom attribute value
    public function getCustomAttributeValue($attributeName)
    {
        $attribute = Attribute::where('name', $attributeName)->first();

        if (!$attribute) {
            return null;
        }

        $value = $this->attributeValues()
            ->where('attribute_id', $attribute->id)
            ->first();

        return $value ? $value->value : null;
    }

    // Helper method to set custom attribute value
    public function setCustomAttributeValue($attributeName, $value): void
    {
        $attribute = Attribute::where('name', $attributeName)->first();

        if (!$attribute) {
            return;
        }

        $this->attributeValues()->updateOrCreate(
            ['attribute_id' => $attribute->id],
            ['value' => $value]
        );

        // Also update the JSON column for faster searches
        $customAttributes = $this->custom_attributes ?: [];
        $customAttributes[$attributeName] = $value;
        $this->custom_attributes = $customAttributes;
        $this->save();
    }



}
