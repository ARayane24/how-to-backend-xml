<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'problem',
        'description',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function steps()
    {
        return $this->hasMany(Step::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    // Helper method to get vote count
    public function getPositiveVotesAttribute()
    {
        return $this->votes()->where('is_positive', true)->count();
    }

    public function getNegativeVotesAttribute()
    {
        return $this->votes()->where('is_positive', false)->count();
    }
}
