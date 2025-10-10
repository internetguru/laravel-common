export default () => ({
    printHtml(html, styles = '') {
        // replace url adding /print
        window.history.pushState({}, '', window.location.href)
        // replace content with html
        document.body.innerHTML = `
        <html>
            <head>
                <title>Print</title>
                <style type="text/css">${styles}</style>
            </head>
            <body>${html}</body>
        </html>`

        const script = document.createElement("script")
        script.innerHTML += `
        function docReady (fn) {
            if (document.readyState === "complete" || document.readyState === "interactive") {
                setTimeout(fn, 1)
            } else {
                document.addEventListener("DOMContentLoaded", fn)
            }
        }

        try {
            docReady(() => { setTimeout(() => {
                window.print()
                window.location.reload()
            }, 250) })
        } catch (ex) {
            console.log(ex)
        }
        `
        document.body.appendChild(script)
        document.body.classList.add('print-dialog')
    },

    printElement(querySelector, styles = '') {
        const element = document.querySelector(querySelector)
        if (! element) {
            console.error(`Element not found: ${querySelector}`)
            return
        }
        this.printHtml(element.innerHTML, styles)
    }
})
