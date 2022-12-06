window.addEventListener("load", () => {

    const notes = document.getElementById("notes");
    const saveNotes = document.getElementById("save-notes");

    notes.addEventListener("input", function() {
        this.classList.remove("saved");
        this.classList.add("edited");
    });

    saveNotes.addEventListener("click", function() {
        if ("path" in this.dataset && "lesson" in this.dataset && "user" in this.dataset) {
            const data = extractData.call(this);
            sendUpdateRequest.call(this, data);
        }
    });

    function extractData() {
        return {
            lesson: this.dataset.lesson,
            user: this.dataset.user,
            text: notes.value
        };
    }

    function sendUpdateRequest(data) {
        axios({
            url: this.dataset.path,
            method: "PATCH",
            data: data
        }).then((response) => {
            if (response.data.status) {
                notes.classList.remove("edited");
                notes.classList.add("saved");
            }
        });
    }

});