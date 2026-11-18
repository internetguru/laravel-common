<?php

namespace InternetGuru\LaravelCommon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AssociationHistory extends Model
{
    protected $fillable = [
        'column_name',
        'column_prev_value',
        'author_id',
    ];

    public function associable(): MorphTo
    {
        return $this->morphTo();
    }

    public function author(): BelongsTo
    {
        /** @var class-string<Model> $userModel */
        $userModel = config('auth.providers.users.model');

        return $this->belongsTo($userModel, 'author_id');
    }
}
