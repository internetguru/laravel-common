<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use InternetGuru\LaravelCommon\Models\AssociationHistory as AssociationHistoryModel;

class AssociationHistory extends Component
{
    public $groups;

    /**
     * Resolved foreign key labels, keyed by "{column}:{value}".
     *
     * @var array<string, ?string>
     */
    private array $resolvedLabels = [];

    public function __construct(Model $model, int $limit = 10)
    {
        // Newest first; the key breaks ties between entries written within the
        // same second (a single update writes all its entries at once).
        $histories = $model->associationHistories()
            ->with('author')
            ->latest()
            ->latest((new AssociationHistoryModel)->getQualifiedKeyName())
            ->limit($limit)
            ->get();

        // Derive new_value for each entry from the history chain.
        $columnPrefix = config('ig-common.association_history.columns.' . get_class($model));
        $casts = $model->getCasts();
        $currentValues = [];
        $originals = $model->getRawOriginal();
        foreach ($histories as $history) {
            $field = $history->column_name;
            if (! isset($currentValues[$field])) {
                if (array_key_exists($field, $originals)) {
                    $currentValues[$field] = (string) ($originals[$field] ?? '');
                } else {
                    $currentValues[$field] = (string) ($model->getAttribute($field) ?? '');
                }
            }
            $history->new_value = $currentValues[$field];
            $history->is_complex = is_array(json_decode($history->column_prev_value ?? '', true))
                || is_array(json_decode($history->new_value ?? '', true));
            $history->is_checkbox = ($casts[$field] ?? null) === 'boolean';
            $history->column_prev_value_translated = $this->displayValue($model, $columnPrefix, $field, $history->column_prev_value);
            $history->new_value_translated = $this->displayValue($model, $columnPrefix, $field, $history->new_value);
            $history->translated_column = $columnPrefix
                ? __("{$columnPrefix}.{$field}")
                : $field;
            $currentValues[$field] = $history->column_prev_value ?? '';
        }

        // Group by author + 10-minute time window
        $groups = [];
        foreach ($histories as $history) {
            $matched = false;
            foreach ($groups as &$group) {
                if ($group['author_id'] === $history->author_id
                    && abs($group['anchor']->diffInMinutes($history->created_at)) <= 10
                ) {
                    $group['entries'][] = $history;
                    $matched = true;
                    break;
                }
            }
            unset($group);
            if (! $matched) {
                $groups[] = [
                    'author_id' => $history->author_id,
                    'author_name' => $history->author?->name,
                    'anchor' => $history->created_at,
                    'time' => $history->created_at,
                    'entries' => [$history],
                    'is_creation' => false,
                ];
            }
        }

        // Merge or append "created" entry
        $createdByField = $model->associationHistoryCreatedBy ?? 'created_by';
        $creatorId = $this->creatorId($model, $createdByField);
        $creator = $creatorId
            ? app(config('auth.providers.users.model'))->find($creatorId)
            : null;
        $createdMerged = false;
        foreach ($groups as &$group) {
            if ($group['author_id'] === $creatorId
                && abs($group['anchor']->diffInMinutes($model->created_at)) <= 10
            ) {
                $group['is_creation'] = true;
                $createdMerged = true;
                break;
            }
        }
        unset($group);
        if (! $createdMerged) {
            $groups[] = [
                'author_id' => $creatorId,
                'author_name' => $creator?->name,
                'anchor' => $model->created_at,
                'time' => $model->created_at,
                'entries' => [],
                'is_creation' => true,
            ];
        }

        // Order entries within each time frame ascending and label the frame
        // with its latest timestamp.
        foreach ($groups as &$group) {
            $group['entries'] = array_reverse($group['entries']);
            foreach ($group['entries'] as $history) {
                if ($history->created_at->greaterThan($group['time'])) {
                    $group['time'] = $history->created_at;
                }
            }
        }
        unset($group);

        $this->groups = $groups;
    }

    /**
     * The value the created-by column held when the model was created: the
     * previous value of its oldest history entry, or the current value when the
     * column was never changed.
     */
    private function creatorId(Model $model, string $createdByField): mixed
    {
        $oldest = $model->associationHistories()
            ->where('column_name', $createdByField)
            ->oldest()
            ->oldest((new AssociationHistoryModel)->getQualifiedKeyName())
            ->first();

        if ($oldest === null) {
            return $model->getAttribute($createdByField);
        }

        $value = $oldest->column_prev_value;
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : $value;
    }

    public function render()
    {
        return view('ig-common::components.association-history');
    }

    /**
     * Resolve a stored value into its human readable form: a related model's
     * label for foreign keys, a translated value otherwise.
     */
    private function displayValue(Model $model, ?string $columnPrefix, string $field, ?string $value): ?string
    {
        return $this->resolveRelatedLabel($model, $field, $value)
            ?? $this->translateValue($columnPrefix, $field, $value);
    }

    /**
     * Resolve a foreign key value to the related model's label by looking up a
     * belongs-to relation named after the column ("user_id" => "user",
     * "created_by" => "createdBy"). Returns null when no relation matches or the
     * related record is gone, so the caller can fall back to the raw value.
     */
    private function resolveRelatedLabel(Model $model, string $field, ?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cacheKey = "{$field}:{$value}";
        if (array_key_exists($cacheKey, $this->resolvedLabels)) {
            return $this->resolvedLabels[$cacheKey];
        }

        $this->resolvedLabels[$cacheKey] = null;

        foreach ($this->relationCandidates($model, $field) as $candidate) {
            if (! method_exists($model, $candidate)) {
                continue;
            }

            try {
                $relation = $model->{$candidate}();
            } catch (\Throwable) {
                continue;
            }

            if (! $relation instanceof BelongsTo || $relation->getForeignKeyName() !== $field) {
                continue;
            }

            $query = $relation->getRelated()->newQuery();
            if (in_array(SoftDeletes::class, class_uses_recursive($relation->getRelated()), true)) {
                $query->withTrashed();
            }

            $related = $query->where($relation->getOwnerKeyName(), $value)->first();
            if ($related !== null) {
                $this->resolvedLabels[$cacheKey] = $this->modelLabel($related);
            }

            break;
        }

        return $this->resolvedLabels[$cacheKey];
    }

    /**
     * Relation method names worth trying for a given column.
     *
     * @return array<int, string>
     */
    private function relationCandidates(Model $model, string $field): array
    {
        $candidates = [];
        if (str_ends_with($field, '_id')) {
            $candidates[] = Str::camel(Str::beforeLast($field, '_id'));
        }
        $candidates[] = Str::camel($field);

        $configured = config('ig-common.association_history.relations.' . get_class($model) . '.' . $field);
        if ($configured) {
            array_unshift($candidates, $configured);
        }

        return array_values(array_unique($candidates));
    }

    /**
     * Human readable label of a related model.
     */
    private function modelLabel(Model $related): string
    {
        if (method_exists($related, 'associationHistoryLabel')) {
            return (string) $related->associationHistoryLabel();
        }

        foreach (['display_name', 'name', 'title', 'label', 'code'] as $attribute) {
            $value = $related->getAttribute($attribute);
            if (is_scalar($value) && (string) $value !== '') {
                return (string) $value;
            }
        }

        return (string) $related->getKey();
    }

    /**
     * Translate an enum-like value, falling back to the raw value when no
     * per-value translation is defined (e.g. free-text or numeric fields).
     */
    private function translateValue(?string $columnPrefix, string $field, ?string $value): ?string
    {
        if (! $columnPrefix || $value === null || $value === '') {
            return $value;
        }

        $key = "{$columnPrefix}.{$field}.{$value}";

        return Lang::has($key) ? __($key) : $value;
    }
}
