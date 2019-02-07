"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const path_1 = require("path");
class MultiThreadedPromiseImpl {
    constructor(maxThreads = 20) {
        this.maxThreads = maxThreads;
        this.actions = [];
        this.threadCount = 0;
        this.resolve = () => { };
        this.ready = false;
        this.promise = new Promise(resolve => {
            this.resolve = resolve;
        });
    }
    enqueue(action) {
        this.actions.push(action);
        this.run();
    }
    done() {
        this.ready = true;
        this.check();
        return this.promise;
    }
    run() {
        if (this.threadCount < this.maxThreads) {
            let action = this.actions.shift();
            if (action) {
                this.threadCount++;
                action().then(() => {
                    this.threadCount--;
                    this.check();
                    this.run();
                });
            }
        }
    }
    check() {
        if (this.ready && this.threadCount === 0) {
            path_1.resolve();
        }
    }
}
exports.default = MultiThreadedPromiseImpl;
//# sourceMappingURL=multithreaded-promise.js.map