@if (count($groups) > 0)
    <dl class="mb-0">
        @foreach ($groups as $group)
            <dt>
                <time style="font-family: monospace">{{ $group['time']->toDisplayTimezone()->dateTimeForHumans() }}</time>
                |
                {{ $group['author_name'] ?? __('ig-common::messages.association_history.guest') }}
            </dt>
            @if (empty($group['entries']))
                <dd>@lang('ig-common::messages.association_history.created')</dd>
            @else
                @foreach ($group['entries'] as $history)
                    <dd>
                        @if ($history->is_complex)
                            <em>{{ $history->column_name }}</em>
                            @continue
                        @endif
                        @if ($history->column_prev_value == null || $history->column_prev_value == '')
                            @lang('ig-common::messages.association_history.added', [
                                'column' => $history->column_name,
                                'value' => Str::limit($history->new_value, 20),
                            ])
                            @continue
                        @endif
                        @if ($history->new_value == null || $history->new_value == '')
                            @lang('ig-common::messages.association_history.removed', [
                                'column' => $history->column_name,
                                'value' => Str::limit($history->column_prev_value, 20),
                            ])
                            @continue
                        @endif
                        @lang('ig-common::messages.association_history.changed', [
                            'column' => $history->column_name,
                            'from' => Str::limit($history->column_prev_value, 20),
                            'to' => Str::limit($history->new_value, 20),
                        ])
                    </dd>
                @endforeach
            @endif
        @endforeach
    </dl>
@else
    <p class="text-muted">@lang('ig-common::messages.association_history.empty')</p>
@endif
