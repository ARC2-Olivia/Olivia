import '../styles/modal.css';

const minutes = 110;
const delay = minutes * 60 * 1000;

function extendSession(path, trans)
{
    if (!("title" in trans)) trans.title = "Session is expiring soon";
    if (!("text" in trans)) trans.title = "Do you want to extend the session?";
    if (!("cancel" in trans)) trans.title = "Cancel";
    if (!("extend" in trans)) trans.title = "Extend";

    const parser = new DOMParser();
    const modal = parser.parseFromString(`
        <div class="modal visible">
            <dialog class="modal-dialog" open>
                <div class="modal-dialog-heading"><h3>${trans.title}</h3></div>
                <div class="modal-dialog-content">${trans.text}</div>
                <form method="dialog" class="modal-dialog-actions">
                    <button class="btn btn-link" value="cancel">${trans.cancel}</button>
                    <button class="btn btn-theme-white bg-blue" value="extend">${trans.extend}</button>
                </form>
            </dialog>
        </div>
    `, "text/html").body.firstChild;
    const dialog = modal.querySelector("dialog");

    dialog.addEventListener("close", async (evt) => {
        if ("extend" === evt.target.returnValue) {
            const response = await fetch(path, { method: "GET" });
            const text = await response.text();
            console.log(text);
            setTimeout(extendSession, delay, path, trans);
        }
        modal.remove();
    })

    document.body.prepend(modal);
}

window.addEventListener("load", () => {
    // Extending session
    let translations = document.body.dataset.extendSessionTranslations || false;
    const path = document.body.dataset.extendSessionPath || false;
    if (false !== translations && false !== path) {
        translations = JSON.parse(translations);
        document.body.removeAttribute("data-extend-session-path");
        document.body.removeAttribute("data-extend-session-translations");
        setTimeout(extendSession, delay, path, translations);
    }
});