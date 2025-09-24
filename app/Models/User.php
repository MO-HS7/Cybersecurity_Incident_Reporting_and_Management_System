<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function networkLogs()
    {
        return $this->hasMany(NetworkLog::class);
    }

    public function alerts()
    {
        return $this->belongsToMany(Alert::class, 'user_alerts')
                    ->withPivot('assigned_at')
                    ->withTimestamps();
    }

    public function notificationPreferences()
    {
        return $this->hasOne(UserNotificationPreference::class);
    }

    // إنشاء تفضيلات الإشعارات تلقائياً
    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            $user->notificationPreferences()->create([
                'email_alerts' => true,
                'browser_notifications' => true,
                'sound_notifications' => true,
                'critical_alerts_only' => false,
                'alert_types' => ['critical', 'high', 'medium', 'low']
            ]);
        });
    }
}
