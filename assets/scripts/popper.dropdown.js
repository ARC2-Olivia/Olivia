import { createPopper } from '@popperjs/core';
import "../styles/dropdown.css";

window.addEventListener('load', () => {
    const dropdowns = document.querySelectorAll("[data-dropdown][data-for]");
    for (const dropdown of dropdowns) {
        const binding = document.querySelector(dropdown.dataset.for);
        if (binding) {
            createPopper(binding, dropdown, { placement: "bottom-end", strategy: "fixed"});
            binding.addEventListener("click", () => {
                dropdown.classList.toggle("dropdown-show")
            });
            dropdown.style.minWidth = `${binding.offsetWidth}px`;
        }
    }

    document.body.addEventListener("click", (e) => {
        for (const dropdown of dropdowns) {
            const binding = document.querySelector(dropdown.dataset.for);
            if (dropdown.hasAttribute("data-show") && dropdown !== e.target && binding && binding !== e.target && !dropdown.contains(e.target)) {
                dropdown.removeAttribute("data-show");
            }
        }
    });
});