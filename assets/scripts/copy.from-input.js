window.addEventListener("DOMContentLoaded", () => {
    for (const elem of document.querySelectorAll("[data-copy-from-input]")) {
        elem.addEventListener("click", (evt) => {
            const input = document.querySelector(evt.target.dataset.copyFromInput);
            if (input) navigator.clipboard.writeText(input.value);
        });
    }
    for (const elem of document.querySelectorAll("[data-copy-from-element]")) {
        elem.addEventListener("click", (evt) => {
            const input = document.querySelector(evt.target.dataset.copyFromElement);
            if (input) navigator.clipboard.writeText(input.innerText);
        });
    }
});