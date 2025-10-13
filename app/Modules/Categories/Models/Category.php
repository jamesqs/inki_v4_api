<?php

namespace App\Modules\Categories\Models;

use App\Modules\Attributes\Models\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static paginate()
 * @method static create(mixed $validated)
 */

class Category extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'slug', 'description'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Define your attribute casts here
    ];

    public function estates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Estate::class);
    }

    public function attributes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Attribute::class)
            ->withPivot('required', 'order')
            ->withTimestamps()
            ->orderBy('pivot_order');
    }
}
