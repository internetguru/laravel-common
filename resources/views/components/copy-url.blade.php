<a
    href="{{ $url }}"
    data-testid="copy-url"
    x-data="{
        url: @js($url),
        error: @js(__('ig-common::layouts.copy_url.error')),
        copied: false,
        async copy() {
            try {
                await navigator.clipboard.writeText(this.url)
                this.copied = true
                setTimeout(() => this.copied = false, 2000)
            } catch (e) {
                console.error(e)
                if (window.Livewire) {
                    window.Livewire.dispatch('ig-message', { type: 'error', message: this.error })
                    return
                }
                window.alert(this.error)
            }
        },
    }"
    x-on:click.prevent="copy()"
    {{ $attributes }}
>@if ($icon)<i class="{{ $icon }}" x-show="! copied"></i><i class="{{ $copiedIcon }}" x-show="copied" x-cloak></i> @endif{{ $slot->isNotEmpty() ? $slot : __('ig-common::layouts.copy_url.link') }}</a>
