window.addEventListener("load", () => {
    document.querySelectorAll(".toast-close").forEach(function(el) {
        el.onclick = function() {
            const toasts = this.closest(".toasts");
            this.closest(".toast").remove();
            console.log(toasts);
            if (toasts !== null && toasts.children.length === 0) {
                toasts.remove();
            }
        }
    });

    const toasts = document.querySelector(".toasts");
    if (toasts && toasts.children.length === 0) {
        toasts.remove();
    }
});