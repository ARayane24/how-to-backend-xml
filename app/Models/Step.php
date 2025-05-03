<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Step extends Model
{
    use HasFactory;

    // No created_at column in this table
    const CREATED_AT = null;

    protected $fillable = [
        'topic_id',
        'title',
        'description',
    ];

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }
}
