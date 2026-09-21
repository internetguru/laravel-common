{{-- Written without a Blade conditional or stray whitespace: a label is rendered
     inside Livewire components and into table cells, where the compiler's own
     markers and every line break would be carried along with it. --}}
<span {{ $attributes->class(['ig-label', 'ig-label-slim' => $slim, 'ig-label-icon' => $icon]) }} style="--ig-label-dot: {{ $dotColor }}">{!! $iconMarkup !!}{{ isset($slot) && ! $slot->isEmpty() ? $slot : $text }}</span>
