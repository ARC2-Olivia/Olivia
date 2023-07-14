import Quill from "quill";

global.Quill = Quill;
global.parser = new DOMParser();

class QuillUtils {
    static #parser = new DOMParser();

    static assignHtmlEditingCapabilities(quill, button, translation) {
        const textCancel = translation.textCancel || 'Cancel';
        const textSubmit = translation.textSubmit || 'Submit';

        button.addEventListener("click", () => {
            const component = parser.parseFromString(`
                <div class="ql-html-backdrop">
                    <div class="ql-html-dialog">
                        <textarea class="ql-html-editor">${quill.root.innerHTML}</textarea>
                        <div class="ql-html-actions">
                            <button type="button" value="cancel" class="btn btn-theme-white bg-blue">${textCancel}</button>
                            <button type="button" value="submit" class="btn btn-theme-white bg-green">${textSubmit}</button>
                        </div>
                    </div>
                </div>
            `, "text/html").body.firstChild;
            const editor = component.querySelector("textarea.ql-html-editor");

            component.querySelector("button[value='cancel']")?.addEventListener("click", (evt) => {
                evt.target.closest(".ql-html-backdrop").remove();
            });

            component.querySelector("button[value='submit']")?.addEventListener("click", (evt) => {
                quill.root.innerHTML = editor.value;
                evt.target.closest(".ql-html-backdrop").remove();
            });

            document.body.appendChild(component);
        });
    }
}

global.QuillUtils = QuillUtils;

import "quill/dist/quill.snow.css";
import "./styles/quill.html-editor.js.css";

