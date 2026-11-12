// In 'nest' mode the <i> keeps the server's markup beside its <svg>, so a morph can skip unchanged icons
const ICON_SELECTOR = 'i[class*="fa-"]';
const sources = new WeakMap();
const toConvert = new Set();

function record(root) {
    const icons = root.matches?.(ICON_SELECTOR) ? [root] : [];
    root.querySelectorAll?.(ICON_SELECTOR).forEach((icon) => icons.push(icon));
    icons.forEach((icon) => {
        if (!sources.has(icon) && !icon.hasAttribute('data-fa-i2svg')) {
            sources.set(icon, icon.outerHTML);
        }
    });
}

function onLivewire(callback) {
    if (window.Livewire) {
        callback(window.Livewire);
        return;
    }
    document.addEventListener('livewire:init', () => callback(window.Livewire));
}

// After the morph patched the changed icons, convert them again, once per parent
function convertLater(icon, dom) {
    if (toConvert.size === 0) {
        queueMicrotask(() => {
            const parents = new Set([...toConvert].map((el) => el.parentNode).filter(Boolean));
            toConvert.clear();
            parents.forEach((parent) => dom.i2svg({ node: parent }));
        });
    }
    toConvert.add(icon);
}

// Call with Font Awesome's config and dom before dom.i2svg() and dom.watch()
export default function keepUnchangedIcons({ config, dom }) {
    config.autoReplaceSvg = 'nest';
    record(document.documentElement);
    new MutationObserver((mutations) => mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => node.nodeType === Node.ELEMENT_NODE && record(node));
    })).observe(document.documentElement, { childList: true, subtree: true });

    onLivewire((Livewire) => Livewire.hook('morph.updating', ({ el, toEl, skip }) => {
        if (el.tagName !== 'I' || (!sources.has(el) && !el.hasAttribute('data-fa-i2svg'))) {
            return;
        }
        if (sources.get(el) === toEl.outerHTML) {
            skip();
            return;
        }
        // The morph patches it back to the server's markup, which Font Awesome's observer ignores
        sources.set(el, toEl.outerHTML);
        convertLater(el, dom);
    }));
}
