@props([
    'type' => 'text',
    'name',
    'id' => null,
    'value',
    'options' => [],
    'useoptionkeys' => false,
    'rows' => 10,
    'disabled' => false,
    'checked' => false,
    'clearable' => true,
    'showError' => true,
])

@php
    // Several copies of the same form can share a page - one per row, one per dialog - and
    // only the element id has to tell them apart. It follows the field name unless the
    // caller has something more specific to say.
    $id = $id ?? $name;
@endphp

<div
    @class(["mt-3", "form-floating" => $type !== "checkbox"])
    @if($clearable) x-data="clearable" @endif
>
    @if ($type === 'textarea')
        <textarea
            id="{{ $id }}"
            name="{{ $name }}"
            placeholder="{{ $slot }}"
            style="min-height: {{ $rows }}rem; max-height: {{ $rows * 3 }}rem; overflow-y: auto;"
            data-testid="input-{{ $id }}"
            @if ($disabled) disabled @endif
            x-data
            x-init="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
            x-on:input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
            {{ $attributes->merge(['class' => 'form-control' . ((isset($errors) && $errors->has($name)) ? ' is-invalid' : '')]) }}
        >{{ old($name) ?? $value ?? '' }}</textarea>
    @elseif ($type === 'select')
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            placeholder="{{ $slot }}"
            data-testid="input-{{ $id }}"
            @if ($disabled) disabled @endif
            {{ $attributes->merge(['class' => 'form-control' . ((isset($errors) && $errors->has($name)) ? ' is-invalid' : '')]) }}
        >
            @foreach($options as $key => $option)
                @if (is_array($option))
                    <option value="{{ $option['id'] }}" @if($option['id'] == (old($name) ?? $value ?? '')) selected @endif>{{ $option['name'] }}</option>
                @elseif ($useoptionkeys)
                    <option value="{{ $key }}" @if($key == (old($name) ?? $value ?? '')) selected @endif>{{ $option }}</option>
                @else
                    <option value="{{ $option }}" @if($option == (old($name) ?? $value ?? '')) selected @endif>{{ $option }}</option>
                @endif
            @endforeach
        </select>
    @elseif ($type == 'checkbox')
        <label><input
            type="{{ $type }}"
            id="{{ $id }}"
            name="{{ $name }}"
            data-testid="input-{{ $id }}"
            @if (old($name) ?? $checked) checked @endif
            @if ($disabled) disabled @endif
            {{ $attributes->merge(['class' => 'form-check-input me-2' . ((isset($errors) && $errors->has($name)) ? ' is-invalid' : '')]) }}
        />{{ $slot }}</label>
    @else
        @php
            $autocomplete = match($type) {
                'url', 'tel', 'email' => $type,
                'password' => 'current-password',
                default => 'off',
            };
        @endphp
        <input
            type="{{ $type }}"
            id="{{ $id }}"
            name="{{ $name }}"
            placeholder="{{ $slot }}"
            data-testid="input-{{ $id }}"
            @if ($type !== 'password') value="{{ old($name) ?? $value ?? '' }}" @endif
            @if ($disabled) disabled @endif
            {{ $attributes->merge(['autocomplete' => $autocomplete, 'class' => 'form-control' . ((isset($errors) && $errors->has($name)) ? ' is-invalid' : '')]) }}
        />
    @endif
    @if ($type !== 'hidden')
        @if ($type !== 'checkbox')
            <label for="{{ $id }}">{{ $slot }}@if($type == 'select')<span>▼</span>@endif</label>
        @endif
        @if ($showError)
            @error($name)
                <span class="invalid-feedback" role="alert" data-testid="input-error-{{ $id }}">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        @endif
    @endif
</div>
