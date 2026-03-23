<?php

namespace InternetGuru\LaravelCommon\Traits;

use InternetGuru\LaravelCommon\Models\AssociationHistory as AssociationHistoryModel;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait AssociationHistory
{
    protected array $associationHistoryChanges = [];

    public function associationHistories(): MorphMany
    {
        return $this->morphMany(AssociationHistoryModel::class, 'associable');
    }

    public static function bootAssociationHistory(): void
    {
        static::updating(function ($model) {
            $model->associationHistoryChanges = [];

            $tracked = $model->associationHistoryTracked ?? [];

            foreach ($tracked as $field) {
                if ($model->isDirty($field)) {
                    $model->associationHistoryChanges[$field] = $model->getRawOriginal($field);
                }
            }
        });

        static::updated(function ($model) {
            if (empty($model->associationHistoryChanges)) {
                return;
            }

            foreach ($model->associationHistoryChanges as $field => $originalValue) {
                $model->associationHistories()->create([
                    'column_name' => $field,
                    'column_prev_value' => self::castHistoryValue($originalValue),
                    'author_id' => auth()->id(),
                ]);
            }

            $model->associationHistoryChanges = [];
        });
    }

    private static function castHistoryValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }
}
