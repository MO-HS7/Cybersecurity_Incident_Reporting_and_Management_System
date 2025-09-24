<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NetworkLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'upload_date',
        'status'
    ];

    protected $casts = [
        'upload_date' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }
}
