<button type="submit" {{ $attributes->merge(['class' => 'btn btn-primary mt-3']) }}>
    {{ strlen($slot) ? $slot : 'Odeslat' }}
</button>
