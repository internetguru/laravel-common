<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\View\Component;
use InternetGuru\LaravelCommon\Models\AssociationHistory as AssociationHistoryModel;

class AssociationHistory extends Component
{
    public $groups;

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
            $history->column_prev_value_translated = $this->translateValue($columnPrefix, $field, $history->column_prev_value);
            $history->new_value_translated = $this->translateValue($columnPrefix, $field, $history->new_value);
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
        $creatorId = $model->getAttribute($createdByField);
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

    public function render()
    {
        return view('ig-common::components.association-history');
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
