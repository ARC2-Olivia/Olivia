class AccessibilityZoom
{
    /** @type {number} */
    #zoomLevel = 1.0;

    /** @type {string} */
    #updateRoute = undefined;

    constructor(updateRoute) {
        this.#updateRoute = updateRoute;
        this.#loadDefaultZoomLevel();
        this.#initializeComponents();
        this.#updateZoom();
    }

    /**
     * @returns {void}
     */
    #loadDefaultZoomLevel() {
        let metaZoom = document.querySelector('meta[name="olivia:zoom"]');
        if (metaZoom) {
            const zoomLevel = metaZoom ? metaZoom.getAttribute('content') : '1.0';
            this.#zoomLevel = parseFloat(zoomLevel) || 1.0;
        }
    }

    /**
     * @param {boolean} sendRequest
     * @returns {void}
     */
    #updateZoom(sendRequest = false) {
        document.body.style.transformOrigin = "0 0";
        document.body.style.transform = `scale(${this.#zoomLevel})`;

        if (true === sendRequest && typeof this.#updateRoute === 'string') {
            const formData = new FormData();
            formData.set('zoomLevel', this.#zoomLevel.toString());
            fetch(this.#updateRoute, { method: 'POST', body: formData });
        }
    }

    #initializeComponents() {
        const buttons = document.querySelectorAll("[data-zoom]");
        for (const button of buttons) {
            if ("increase" === button.dataset.zoom) {
                button.addEventListener("click", () => {
                    if (this.#zoomLevel >= 3.0) return;
                    this.#zoomLevel += 0.2;
                    this.#updateZoom(true);
                });
            } else if ("decrease" === button.dataset.zoom) {
                button.addEventListener("click", () => {
                    if (this.#zoomLevel <= 1.0) return;
                    this.#zoomLevel -= 0.2;
                    this.#updateZoom(true);
                });
            } else if ("reset" === button.dataset.zoom) {
                button.addEventListener("click", () => {
                    this.#zoomLevel = 1.0
                    this.#updateZoom(true)
                });
            }
        }
    }
}

window.AccessibilityZoom = AccessibilityZoom;