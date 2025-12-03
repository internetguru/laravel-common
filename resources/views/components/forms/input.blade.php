@props([
    'type' => 'text',
    'name',
    'value',
    'options' => [],
    'useoptionkeys' => false,
    'rows' => 10,
    'disabled' => false,
    'checked' => false,
    'clearable' => true,
    'showError' => true,
])

<div
    @class(["mt-3", "form-floating" => $type !== "checkbox"])
    @if($clearable) x-data="clearable" @endif
>
    @if ($type === 'textarea')
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            placeholder="{{ $slot }}"
            style="height: {{ $rows }}rem"
            data-testid="input-{{ $name }}"
            @if ($disabled) disabled @endif
            {{ $attributes->merge(['class' => 'form-control' . ((isset($errors) && $errors->has($name)) ? ' is-invalid' : '')]) }}
        >{{ old($name) ?? $value ?? '' }}</textarea>
    @elseif ($type === 'select')
        <select
            id="{{ $name }}"
            name="{{ $name }}"
            placeholder="{{ $slot }}"
            data-testid="input-{{ $name }}"
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
            id="{{ $name }}"
            name="{{ $name }}"
            data-testid="input-{{ $name }}"
            @if (old($name) ?? $checked) checked @endif
            @if ($disabled) disabled @endif
            {{ $attributes->merge(['class' => 'form-check-input me-2' . ((isset($errors) && $errors->has($name)) ? ' is-invalid' : '')]) }}
        />{{ $slot }}</label>
    @else
        <input
            type="{{ $type }}"
            id="{{ $name }}"
            name="{{ $name }}"
            placeholder="{{ $slot }}"
            data-testid="input-{{ $name }}"
            @if ($type !== 'password') value="{{ old($name) ?? $value ?? '' }}" @endif
            @if ($disabled) disabled @endif
            {{ $attributes->merge(['class' => 'form-control' . ((isset($errors) && $errors->has($name)) ? ' is-invalid' : '')]) }}
        />
    @endif
    @if ($type !== 'hidden')
        @if ($type !== 'checkbox')
            <label for="{{ $name }}">{{ $slot }}@if($type == 'select')<span>▼</span>@endif</label>
        @endif
        @if ($showError)
            @error($name)
                <span class="invalid-feedback" role="alert" data-testid="input-error-{{ $name }}">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        @endif
    @endif
</div>
