// A failed Livewire request (e.g. an upstream timeout) is shown by Livewire as a
// raw HTML overlay on top of the current page by default. The backend Handler
// renders the same styled error page for a failed Livewire request as it would
// for a normal request (see InternetGuru\LaravelCommon\Exceptions\Handler), so
// here we swap that page in directly instead of letting Livewire draw its overlay.
export default function registerLivewireErrorHandling() {
    // Livewire attaches itself to window.Livewire as soon as its module is
    // imported by the app, before this runs, so it's always available here.
    if (!window.Livewire) {
        return;
    }

    window.Livewire.hook('request', ({ fail }) => {
        fail(({ status, content, preventDefault }) => {
            preventDefault();
            console.error(`Livewire request failed with status ${status}`);

            // only swap in the response when it is a full HTML error page; some
            // error responses are JSON (e.g. 401 from the default auth handling)
            if (content && content.trimStart().startsWith('<')) {
                document.open();
                document.write(content);
                document.close();
                return;
            }

            // network-level failure or a non-HTML error body has no page to
            // show, so fall back to reloading the current page
            window.location.reload();
        });
    });
}
