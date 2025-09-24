<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MLModel extends Model
{
    use HasFactory;

    protected $table = 'ml_models';

    protected $fillable = [
        'name',
        'description',
        'file_path',
        'trained_at',
    ];

    protected $casts = [
        'trained_at' => 'datetime',
    ];

    /**
     * Get the alerts for the ML model.
     */
    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }
}
