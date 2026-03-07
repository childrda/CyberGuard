<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingPage extends Model
{
    protected $fillable = ['name', 'slug', 'type', 'html_content', 'css_content', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function templates(): HasMany
    {
        return $this->hasMany(PhishingTemplate::class, 'training_page_id');
    }
}
