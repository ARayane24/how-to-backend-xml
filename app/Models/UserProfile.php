<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_id',
        'first_name',
        'last_name',
        'profile_picture',
        'bio',
        'joined_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'joined_date' => 'date',
    ];

    /**
     * Set the joined date attribute.
     *
     * @param mixed $value
     * @return void
     */
    public function setJoinedDateAttribute($value)
    {
        if (is_string($value)) {
            try {
                // Try to parse the string as a date
                $this->attributes['joined_date'] = Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $e) {
                // If it fails, use current date
                $this->attributes['joined_date'] = now()->format('Y-m-d');
            }
        } else {
            // If it's already a Carbon instance or null, store it directly
            $this->attributes['joined_date'] = $value;
        }
    }

    /**
     * Get the account that owns the profile.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
