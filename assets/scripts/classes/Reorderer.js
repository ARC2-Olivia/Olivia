class Reorderer {
    #sortable;

    constructor(elementId, handleClass, ghostClass) {
        this.#sortable = new Sortable(document.getElementById(elementId), {
            handle: "." + handleClass,
            animation: 100,
            ghostClass: ghostClass,
            onEnd: this.#onEnd
        });
    }

    #onEnd(event) {
        const parent = event.item.parentElement;
        if ("path" in parent.dataset) {
            const items = Array.from(parent.children);
            let reorders = [];

            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                if ("id" in item.dataset) {
                    reorders.push({id: item.dataset.id, position: i + 1});
                }
            }

            if (reorders.length > 0) {
                axios({ url: parent.dataset.path, method: "post", data: {reorders: reorders} });
            }
        }
    }

}

global.Reorderer = Reorderer;