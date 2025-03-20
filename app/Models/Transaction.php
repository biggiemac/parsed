<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'description',
        'card_member',
        'amount',
        'category_id',
        'ignored',
        'original_csv_row',
        'card_id'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'ignored' => 'boolean'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
