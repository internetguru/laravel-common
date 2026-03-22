<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

class AssociationHistory extends Component
{
    public $histories;

    public function __construct(Model $model, int $limit = 10)
    {
        $histories = $model->associationHistories()
            ->with('author')
            ->latest()
            ->limit($limit)
            ->get();

        // Derive new_value for each entry from the history chain.
        // For the most recent entry per field: new value = current model attribute.
        // For older entries: new value = next (newer) entry's prev_value.
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

        $this->histories = $histories;
    }

    public function render()
    {
        return view('ig-common::components.association-history');
    }
}
