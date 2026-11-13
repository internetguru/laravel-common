// Font Awesome draws an unregistered icon as a pulsing placeholder and says nothing, so name it in the console
const SELECTOR = 'svg[data-icon]';
const PACKAGES = { fas: 'free-solid-svg-icons', far: 'free-regular-svg-icons', fab: 'free-brands-svg-icons' };
const reported = new Set();

function importName(icon) {
    return 'fa' + icon.replace(/(?:^|-)(\w)/g, (match, letter) => letter.toUpperCase());
}

function missingIcons(root) {
    const svgs = root.matches?.(SELECTOR) ? [root] : [];
    root.querySelectorAll?.(SELECTOR).forEach((svg) => svgs.push(svg));
    return svgs.filter((svg) => svg.querySelector('.missing'));
}

function report(svg) {
    const { prefix, icon } = svg.dataset;
    const key = `${prefix} ${icon}`;
    if (reported.has(key)) {
        return;
    }
    reported.add(key);
    const from = PACKAGES[prefix] ? ` from @fortawesome/${PACKAGES[prefix]}` : '';
    console.error(`Font Awesome icon "${icon}" (${prefix}) is not registered: add ${importName(icon)}${from} to library.add().`);
}

export default function reportMissingIcons() {
    missingIcons(document.documentElement).forEach(report);
    const observer = new MutationObserver((mutations) => mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => node.nodeType === Node.ELEMENT_NODE && missingIcons(node).forEach(report));
    }));
    observer.observe(document.documentElement, { childList: true, subtree: true });
    return () => observer.disconnect();
}
