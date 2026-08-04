<a
    href="{{ $url }}"
    data-testid="copy-url"
    x-data="{
        url: @js($url),
        success: @js(__('ig-common::layouts.copy_url.success')),
        error: @js(__('ig-common::layouts.copy_url.error')),
        async copy() {
            try {
                await navigator.clipboard.writeText(this.url)
                this.notify('success', this.success)
            } catch (e) {
                console.error(e)
                this.notify('error', this.error)
            }
        },
        notify(type, message) {
            if (window.Livewire) {
                window.Livewire.dispatch('ig-message', { type: type, message: message })
                return
            }
            window.alert(message)
        },
    }"
    x-on:click.prevent="copy()"
    {{ $attributes }}
>@if ($icon)<i class="{{ $icon }}"></i> @endif{{ $slot->isNotEmpty() ? $slot : __('ig-common::layouts.copy_url.link') }}</a>
