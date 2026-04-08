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
                        @if (! $history->is_complex)
                            @if ($history->column_prev_value !== null && $history->column_prev_value !== '')
                                <em>{{ $history->column_name }}</em>
                                @lang('ig-common::messages.association_history.from') <samp title="{{ $history->column_prev_value }}">{{ Str::limit($history->column_prev_value, 20) }}</samp>
                                @lang('ig-common::messages.association_history.to') <samp title="{{ $history->new_value ?? '–' }}">{{ Str::limit($history->new_value ?? '–', 20) }}</samp>
                            @else
                                @lang('ig-common::messages.association_history.added') <em>{{ $history->column_name }}</em>
                            @endif
                        @else
                            <em>{{ $history->column_name }}</em>
                        @endif
                    </dd>
                @endforeach
            @endif
        @endforeach
    </dl>
@else
    <p class="text-muted">@lang('ig-common::messages.association_history.empty')</p>
@endif
