@if (count($groups) > 0)
    <dl class="mb-0">
        @foreach ($groups as $group)
            <dt>
                <time style="font-family: monospace">{{ $group['time']->toDisplayTimezone()->dateTimeForHumans() }}</time>
                @lang('ig-common::messages.association_history.by')
                {{ $group['author_name'] ?? __('ig-common::messages.association_history.guest') }}
            </dt>
            @if ($group['is_creation'])
                <dd>@lang('ig-common::messages.association_history.created')</dd>
            @endif
            @foreach ($group['entries'] as $history)
                <dd>
                    @if ($history->is_complex)
                        @lang('ig-common::messages.association_history.changed-simple', [
                            'column' => $history->translated_column,
                        ])
                        @continue
                    @endif
                    @if ($history->is_checkbox)
                        @if ((string) $history->new_value === '1')
                            @lang('ig-common::messages.association_history.checked', [
                                'column' => $history->translated_column,
                            ])
                        @else
                            @lang('ig-common::messages.association_history.unchecked', [
                                'column' => $history->translated_column,
                            ])
                        @endif
                        @continue
                    @endif
                    @if ($history->column_prev_value == null || $history->column_prev_value == '')
                        @lang('ig-common::messages.association_history.added', [
                            'column' => $history->translated_column,
                            'value' => Str::limit($history->new_value_translated, 40),
                        ])
                        @continue
                    @endif
                    @if ($history->new_value == null || $history->new_value == '')
                        @lang('ig-common::messages.association_history.removed', [
                            'column' => $history->translated_column,
                            'value' => Str::limit($history->column_prev_value_translated, 40),
                        ])
                        @continue
                    @endif
                    @lang('ig-common::messages.association_history.changed', [
                        'column' => $history->translated_column,
                        'from' => Str::limit($history->column_prev_value_translated, 40),
                        'to' => Str::limit($history->new_value_translated, 40),
                    ])
                </dd>
            @endforeach
        @endforeach
    </dl>
@else
    <p class="text-muted">@lang('ig-common::messages.association_history.empty')</p>
@endif
