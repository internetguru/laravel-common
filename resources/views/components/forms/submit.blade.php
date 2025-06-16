<button type="submit" {{ $attributes->merge(['class' => 'btn btn-primary mt-3']) }} data-testid="submit-button">
    {{ strlen($slot) ? $slot : 'Odeslat' }}
</button>
