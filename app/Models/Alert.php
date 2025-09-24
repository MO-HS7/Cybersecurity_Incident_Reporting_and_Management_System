<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'network_log_id',
        'ml_model_id',
        'attack_type',
        'severity',
        'source_ip',
        'destination_ip',
        'confidence_score',
        'status',
        'detected_at',
        'description'
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'confidence_score' => 'decimal:2'
    ];

    public function networkLog()
    {
        return $this->belongsTo(NetworkLog::class);
    }

    public function mlModel()
    {
        return $this->belongsTo(MLModel::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_alerts')
                    ->withPivot('assigned_at')
                    ->withTimestamps();
    }
}
