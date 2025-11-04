<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;


class Receipt extends Model
{
    protected $fillable = [
        'merchant',
        'amount',
        'date',
        'image_path',
        'status',
        'ocr_data',
        'user_id',
    ];

    protected $casts = [
        'ocr_data' => 'array',
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
