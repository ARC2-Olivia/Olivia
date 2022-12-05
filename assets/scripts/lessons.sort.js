window.addEventListener("load", () => {

    new Sortable(document.getElementById("lesson-items"), {
        handle: ".lesson-item-reorder-handle",
        animation: 100,
        ghostClass: 'reordering',
        onEnd: (event) => {
            const parent = event.item.parentElement;
            if ("path" in parent.dataset) {
                let reorders = readReorderData(parent);
                sendReorderRequest(parent.dataset.path, reorders);
            }
        }
    });

    function readReorderData(parent) {
        const items = Array.from(parent.children);
        let reorders = [];
        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            if ("id" in item.dataset) {
                reorders.push({id: item.dataset.id, position: i + 1});
            }
        }
        return reorders;
    }

    function sendReorderRequest(path, reorders) {
        if (reorders.length > 0) {
            axios({
                url: path,
                method: "post",
                data: {reorders: reorders}
            });
        }
    }

});