import { resolve } from "path";

interface MultiThreadedPromise {
    enqueue(...actions: (() => PromiseLike<void>)[]): void;
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

    enqueue(...actions: (() => PromiseLike<void>)[]) {
        for (const action of actions) {
            this.actions.push(action);
            this.run();
        }
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
        if (this.ready && this.threadCount === 0 && this.actions.length === 0) {
            this.resolve();
        }
    }
}