<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingModule extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'duration_minutes', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
