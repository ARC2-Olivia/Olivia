function updateTab(activeTab, tabs) {
    for (const tab of tabs) {
        if (tab === activeTab) tab.classList.remove("hide");
        else tab.classList.add("hide")
    }
}

function updateTabTriggers(activeTabTrigger, tabTriggers) {
    if (activeTabTrigger.classList.contains("active")) return;
    for (const tabTrigger of tabTriggers) {
        if (tabTrigger === activeTabTrigger) tabTrigger.classList.add("active");
        else tabTrigger.classList.remove("active");
    }
}

window.addEventListener("DOMContentLoaded", () => {
    const tabTriggers = document.querySelectorAll("[data-tab-trigger]");
    const tabs = document.querySelectorAll("[data-tab]");
    for (const tabTrigger of tabTriggers) {
        tabTrigger.addEventListener("click", (e) => {
            const tab = document.querySelector(e.target.dataset.tabTrigger);
            updateTab(tab, tabs);
            updateTabTriggers(e.target, tabTriggers);
        })
    }
});