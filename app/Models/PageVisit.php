<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageVisit extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'visit_date', 'visitor_hash', 'user_id', 'country_code', 'region', 'city', 'path',
    ];

    protected $casts = [
        'visit_date' => 'date',
    ];
}
