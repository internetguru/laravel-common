<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

class AssociationHistory extends Component
{
    public $histories;

    public function __construct(Model $model)
    {
        $histories = $model->associationHistories()
            ->with('author')
            ->latest()
            ->get();

        // Derive new_value for each entry from the history chain.
        // For the most recent entry per field: new value = current model attribute.
        // For older entries: new value = next (newer) entry's prev_value.
        $currentValues = [];
        foreach ($histories as $history) {
            $field = $history->column_name;
            if (! isset($currentValues[$field])) {
                $currentValues[$field] = (string) ($model->getRawOriginal($field) ?? '');
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
