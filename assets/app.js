import './app.css';
import './scripts/toast'

window.addEventListener("load", () => {
    // Menu buttons
    const menuButton = document.querySelector(".navigation-responsive > .navigation-open");
    const menu = document.querySelector(".navigation-responsive > .navigation-main");
    menuButton.addEventListener("click", () => {
        menu.classList.toggle("show");
    });
});