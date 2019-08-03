export interface ThreadPool {
    enqueue(...actions: (() => PromiseLike<void>)[]): Promise<void>;
    enqueueAll(actions: () => Iterable<(() => PromiseLike<void>)>): Promise<void>;
}

export class ThreadPoolImpl implements ThreadPool {

    private actions: (() => PromiseLike<void>)[] = [];
    private _threadCount: number = 0;

    constructor(private maxThreads: number = 20) { }

    enqueue(...actions: (() => PromiseLike<void>)[]) : Promise<void> {
        return new Promise(resolve => {
            var myThreadCount = actions.length;
            this.actions.push(...actions.map(action => async() => {
                await action();
                this._threadCount--;
                if (--myThreadCount === 0) {
                    resolve();
                }
                this.run();
            }));
            this.run();
        });
    }

    enqueueAll(actions: () => Iterable<(() => PromiseLike<void>)>) {
        return this.enqueue(...actions());
    }

    
    private run() {
        var action;
        while (this._threadCount < this.maxThreads && (action = this.actions.shift())) {
            this._threadCount++;
            action();
        }
    }
}