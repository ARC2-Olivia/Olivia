window.addEventListener("load", () => {

    document.querySelectorAll(".completion-marker-outer").forEach((button) => {
        button.addEventListener("click", function() {
            if ("path" in this.dataset && "textDone" in this.dataset && "textUndone" in this.dataset) {
                sendToggleCompletionRequest(this);
            }
        });
        button.customMethods = {
            markAs: function(action) {
                if ("done" === action) {
                    button.customMethods.markAsDone();
                } else if ("undone" === action) {
                    button.customMethods.markAsNotDone();
                }
            },
            markAsDone: function() {
                button.classList.remove('btn-thematic-orange-outline');
                button.classList.add('btn-theme-white', 'bg-green');
                button.innerText = button.dataset.textDone || 'Done';
            },
            markAsNotDone: function() {
                button.classList.remove('btn-theme-white', 'bg-green');
                button.classList.add('btn-thematic-orange-outline');
                button.innerText = button.dataset.textUndone || 'Mark as done';
            }
        };
    });

    function sendToggleCompletionRequest(element) {
        axios({url: element.dataset.path, method: "PATCH"}).then((response) => {
            console.log(response.data);
            if (response.data.success) {
                console.log(element.dispatchEvent(new CustomEvent("lesson-completion-update", { detail: response.data, bubbles: true })));
            }
        });
    }

});