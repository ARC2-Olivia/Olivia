document.querySelectorAll(".toast-close").forEach(function(el) {
    el.onclick = function() {
        this.closest(".toast").remove();
    }
});