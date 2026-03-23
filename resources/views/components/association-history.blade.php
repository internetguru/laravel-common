@if ($histories->isNotEmpty())
    <ul class="list-unstyled mb-0">
        @foreach ($histories as $history)
            <li class="mb-1">
                <span class="text-muted">{{ $history->created_at->toDisplayTimezone()->dateTimeForHumans() }}</span>
                <strong>{{ $history->author?->name ?? __('ig-common::messages.association_history.guest') }}</strong>
                @lang('ig-common::messages.association_history.edited') <em>{{ $history->column_name }}</em>
                @if (! $history->is_complex)
                    @lang('ig-common::messages.association_history.from') <samp>{{ $history->column_prev_value ?? '–' }}</samp>
                    @lang('ig-common::messages.association_history.to') <samp>{{ $history->new_value ?? '–' }}</samp>
                @endif
            </li>
        @endforeach
    </ul>
@else
    <p class="text-muted">@lang('ig-common::messages.association_history.empty')</p>
@endif
