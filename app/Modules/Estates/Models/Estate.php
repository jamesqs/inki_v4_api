<?php

namespace App\Modules\Estates\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Modules\Categories\Models\Category;
use App\Modules\Attributes\Models\Attribute;
use App\Modules\Locations\Models\Location;
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
        'name', 'slug', 'description', 'price', 'location_id', 'district_id', 'category_id',
        'accepted', 'sold', 'published', 'address', 'zip', 'custom_attributes'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'custom_attributes' => 'array',
        'price' => 'decimal:2'
    ];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function location(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function attributeValues(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EstateAttributeValue::class);
    }

    // Helper method to get attribute value
    public function getAttributeValue($attributeName)
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

    // Helper method to set attribute value
    public function setAttributeValue($attributeName, $value): void
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
