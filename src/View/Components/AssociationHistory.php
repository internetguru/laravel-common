<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

class AssociationHistory extends Component
{
    public $groups;

    public function __construct(Model $model, int $limit = 10)
    {
        $histories = $model->associationHistories()
            ->with('author')
            ->latest()
            ->limit($limit)
            ->get();

        // Derive new_value for each entry from the history chain.
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
            $currentValues[$field] = $history->column_prev_value ?? '';
        }

        // Group by author + 10-minute time window
        $groups = [];
        foreach ($histories as $history) {
            $matched = false;
            foreach ($groups as &$group) {
                if ($group['author_id'] === $history->author_id
                    && abs($group['time']->diffInMinutes($history->created_at)) <= 10
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
                    'time' => $history->created_at,
                    'entries' => [$history],
                ];
            }
        }

        // Append "created" entry
        $createdByField = $model->associationHistoryCreatedBy ?? 'created_by';
        $creatorId = $model->getAttribute($createdByField);
        $creator = $creatorId
            ? app(config('auth.providers.users.model'))->find($creatorId)
            : null;
        $groups[] = [
            'author_id' => $creatorId,
            'author_name' => $creator?->name,
            'time' => $model->created_at,
            'entries' => [],
        ];

        $this->groups = $groups;
    }

    public function render()
    {
        return view('ig-common::components.association-history');
    }
}
