<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryRule extends Model
{
    protected $fillable = [
        'category_id',
        'pattern',
        'is_regex',
        'priority'
    ];

    protected $casts = [
        'is_regex' => 'boolean',
        'priority' => 'integer'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function matches(string $description): bool
    {
        if ($this->is_regex) {
            return preg_match($this->pattern, $description) === 1;
        }
        return stripos($description, $this->pattern) !== false;
    }
} 