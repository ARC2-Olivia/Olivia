import '../styles/modal.css';

window.addEventListener('load', () => {
    const modals = document.querySelectorAll('.modal');
    for (const modal of modals) {
        modal.addEventListener("click", function (evt) {
            if (this === evt.target && this.classList.contains("visible")) {
                this.classList.remove("visible");
            }
        });
    }

    const modalOpeners = document.querySelectorAll("[data-modal-open]");
    for (const modalOpener of modalOpeners) {
        modalOpener.addEventListener("click", function (evt) {
            const modal = document.querySelector(this.dataset.modalOpen);
            if (null !== modal) {
                modal.classList.add('visible');
            }
        });
    }

    const modalClosers = document.querySelectorAll("[data-modal-close]");
    for (const modalCloser of modalClosers) {
        modalCloser.addEventListener("click", function (evt) {
            const modal = document.querySelector(this.dataset.modalClose);
            if (null !== modal) {
                modal.classList.remove('visible');
            }
        });
    }
});