import './app.css';
import './styles/accessibility/dyslexic.css'
import './styles/accessibility/contrasted.css'
import './scripts/toast'
import './scripts/accessibility/zoom'

window.addEventListener("load", () => {
    // Menu buttons
    const menuButton = document.querySelector(".navigation-responsive > .navigation-open");
    const menu = document.querySelector(".navigation-responsive > .navigation-main");
    menuButton.addEventListener("click", () => {
        menu.classList.toggle("show");
    });
});