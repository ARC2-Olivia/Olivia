import './scripts/lessons.completion.outer';
import './scripts/lessons.completion.inner';

window.addEventListener("load", () => {
   document.body.addEventListener("lesson-completion-update", function(evt) {
       const completionElements = document.querySelectorAll(`[data-completion-lesson-id="${evt.detail.lesson}"]`);
       for (const completionElement of completionElements) {
           completionElement.customMethods.markAs(evt.detail.action);
       }
   });
});