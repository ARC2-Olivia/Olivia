import '../styles/modal.css';

window.initializeModal = function(element) {
    element.addEventListener("click", function (evt) {
        if (this === evt.target && this.classList.contains("visible")) {
            this.classList.remove("visible");
        }
    });
};

window.Modals = {
    initializeModal: function (element) {
        element.addEventListener("click", function (evt) {
            if (this === evt.target && this.classList.contains("visible")) {
                this.classList.remove("visible");
            }
        });
    },
    initializeModalOpener: function (element) {
        element.addEventListener("click", function (evt) {
            const modal = document.querySelector(this.dataset.modalOpen);
            if (null !== modal) {
                modal.classList.add('visible');
            }
        });
    },
    initializeModalCloser: function (element) {
        element.addEventListener("click", function (evt) {
            const modal = document.querySelector(this.dataset.modalClose);
            if (null !== modal) {
                modal.classList.remove('visible');
            }
        });
    }
}

window.addEventListener('load', () => {
    for (const modal of document.querySelectorAll('.modal')) window.Modals.initializeModal(modal);
    for (const modalOpener of document.querySelectorAll("[data-modal-open]")) window.Modals.initializeModalOpener(modalOpener);
    for (const modalCloser of document.querySelectorAll("[data-modal-close]")) window.Modals.initializeModalCloser(modalCloser);
});