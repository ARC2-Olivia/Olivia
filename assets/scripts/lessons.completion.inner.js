window.addEventListener("load", () => {

    document.querySelectorAll(".completion-marker").forEach((button) => {
        button.addEventListener("click", function() {
            if ("path" in this.dataset) {
                sendToggleCompletionRequest(this);
            }
        });
    });

    function sendToggleCompletionRequest(element) {
        axios({url: element.dataset.path, method: "PATCH"}).then((response) => {
            console.log(response.data);
            if (response.data.success) {
                if (response.data.action === "done") {
                    element.classList.remove('btn-thematic-gray-to-green');
                    element.classList.add('btn-theme-white', 'bg-green');
                } else if (response.data.action === "undone") {
                    element.classList.remove('btn-theme-white', 'bg-green');
                    element.classList.add('btn-thematic-gray-to-green');
                }
            }
        });
    }

});