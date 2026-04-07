@if (count($groups) > 0)
    <dl class="mb-0">
        @foreach ($groups as $group)
            <dt>
                {{ $group['author_name'] ?? __('ig-common::messages.association_history.guest') }}
                @lang('ig-common::messages.association_history.at')
                <span class="text-muted">{{ $group['time']->toDisplayTimezone()->dateTimeForHumans() }}</span>
            </dt>
            @if (empty($group['entries']))
                <dd>@lang('ig-common::messages.association_history.created')</dd>
            @else
                @foreach ($group['entries'] as $history)
                    <dd>
                        <em>{{ $history->column_name }}</em>
                        @if (! $history->is_complex)
                            @if ($history->column_prev_value !== null && $history->column_prev_value !== '')
                                @lang('ig-common::messages.association_history.from') <samp title="{{ $history->column_prev_value }}">{{ Str::limit($history->column_prev_value, 20) }}</samp>
                            @endif
                            @lang('ig-common::messages.association_history.to') <samp title="{{ $history->new_value ?? '–' }}">{{ Str::limit($history->new_value ?? '–', 20) }}</samp>
                        @endif
                    </dd>
                @endforeach
            @endif
        @endforeach
    </dl>
@else
    <p class="text-muted">@lang('ig-common::messages.association_history.empty')</p>
@endif
