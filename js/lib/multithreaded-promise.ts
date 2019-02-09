import { resolve } from "path";

interface MultiThreadedPromise {
    enqueue(action: () => PromiseLike<void>): void;
    done(): PromiseLike<void>;
}
export default class MultiThreadedPromiseImpl implements MultiThreadedPromise {

    private actions: (() => PromiseLike<void>)[] = [];
    private threadCount: number = 0;
    private resolve: (() => void) = () => { };
    private ready = false;
    private promise: PromiseLike<void> = new Promise<void>(resolve => {
        this.resolve = resolve;
    });

    constructor(private maxThreads: number = 20) { }

    enqueue(action: () => PromiseLike<void>) {
        this.actions.push(action);
        this.run();
    }

    done() {
        this.ready = true;
        this.check();
        return this.promise;
    }

    private run() {
        if (this.threadCount < this.maxThreads) {
            let action = this.actions.shift();
            if (action) {
                this.threadCount++;
                action().then(() => {
                    this.threadCount--;
                    this.check()
                    this.run();
                });
            }
        }
    }

    private check() {
        if (this.ready && this.threadCount === 0) {
            this.resolve();
        }
    }
}