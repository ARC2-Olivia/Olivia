class EventBus {
    #listeners;

    constructor() {
        this.#listeners = [];
    }

    attach(object) {
        if (typeof object !== "object" || object === null) {
            throw Error("Only objects can be attached to EventBus.");
        }

        object._eventBus = this;
        object._callbacks = {};
        object.on = function (event, fn) {
            if (typeof fn !== "function") {
                throw Error("Second argument for the 'on' method must be a function.")
            }
            this._callbacks[event] = fn;
        };
        object.off = function (event) {
            if (event in this.callbacks) {
                delete this._callbacks[event];
            }
        };
        object.dispatch = function (event, payload = null) {
            this._eventBus.notifyListeners(event, this, payload);
        };
        object.notify = function (event, sender, payload) {
            const context = this;
            if (event in this._callbacks) {
                this._callbacks[event].apply(context, [sender, payload]);
            }
        }

        this.#listeners.push(object);
    }

    notifyListeners(event, sender, payload) {
        for (const listener of this.#listeners) {
            listener.notify(event, sender, payload);
        }
    }
}

global.EventBus = EventBus;