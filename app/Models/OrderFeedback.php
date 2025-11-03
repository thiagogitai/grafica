<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderFeedback extends Model
{
    protected $table = 'order_feedbacks';

    protected $fillable = [
        'order_id',
        'user_id',
        'rating',
        'headline',
        'comment',
        'metadata',
        'submitted_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
