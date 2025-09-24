<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_alerts',
        'browser_notifications',
        'sound_notifications',
        'critical_alerts_only',
        'alert_types'
    ];

    protected $casts = [
        'email_alerts' => 'boolean',
        'browser_notifications' => 'boolean',
        'sound_notifications' => 'boolean',
        'critical_alerts_only' => 'boolean',
        'alert_types' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
