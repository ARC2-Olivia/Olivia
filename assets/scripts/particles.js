import './_external/particles.js/particles.min.js'

const particleSettings = {
    particles: {
        number: { value: 50, density: { enable: true, value_area: 1000 } },
        color: { value: "#ffffff" },
        size: { value: 2, random: false },
        opacity: { value: 0.33334, random: false},
        line_linked: { enable: true, distance: 250, color: "#ffffff", opacity: 0.33334, width: 1 },
        move: {
            enable: true,
            speed: 6,
            direction: "none",
            random: true,
            straight: false,
            out_mode: "bounce",
            bounce: false,
        }
    },
    retina_detect: true
};

window.addEventListener("DOMContentLoaded", () => {
    const particleElements = document.querySelectorAll("[id][data-particles]")
    for (const pe of particleElements) {
        particlesJS(pe.getAttribute("id"), particleSettings);
    }
});