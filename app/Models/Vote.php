<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'account_id',
        'is_positive',
    ];

    protected $casts = [
        'is_positive' => 'boolean',
    ];

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
