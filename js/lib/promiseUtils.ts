var warningTimer : NodeJS.Timeout;
const MAX_TIMEOUT = 0x7fffffff;

//dummy timeout to keep the Node process alive
export function startup(timeout = MAX_TIMEOUT) {
    warningTimer = warningTimer || setTimeout(() => {}, timeout);    
}

export function shutdown() {
    warningTimer && clearTimeout(warningTimer);
}

export class SleepPromise {
    private _timeout: number = 0;
    private _promise: Promise<void>;

    constructor(millis: number) {
        this._promise = new Promise<void>(resolve => {
            this._timeout = setTimeout(resolve, millis)
        });
    }

    public get promise() {
        return this._promise;
    }

    public cancel() {
        clearTimeout(this._timeout);
    }
}


export const sleep = (millis: number) => new SleepPromise(millis).promise;
