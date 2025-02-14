let zoomLevel = 1.0;

function updateZoom() {
    document.body.style.transformOrigin = "0 0";
    document.body.style.transform = `scale(${zoomLevel})`;
}

window.addEventListener("DOMContentLoaded", () => {
    const buttons = document.querySelectorAll("[data-zoom]");
    for (const button of buttons) {
        if ("increase" === button.dataset.zoom) {
            button.addEventListener("click", () => {
                if (zoomLevel >= 3.0) return;
                zoomLevel += 0.2;
                updateZoom()
            });
        } else if ("decrease" === button.dataset.zoom) {
            button.addEventListener("click", () => {
                if (zoomLevel <= 1.0 ) return;
                zoomLevel -= 0.2;
                updateZoom()
            });
        } else if ("reset" === button.dataset.zoom) {
            button.addEventListener("click", () => {
                zoomLevel = 1.0
                updateZoom()
            });
        }
    }
});