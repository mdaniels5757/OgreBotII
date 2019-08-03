var warningTimer : NodeJS.Timeout;
const MAX_TIMEOUT = 0x7fffffff;

//dummy timeout to keep the Node process alive
export function startup(timeout = MAX_TIMEOUT) {
    warningTimer = warningTimer || setTimeout(() => {}, timeout);    
}

export function shutdown() {
    warningTimer && clearTimeout(warningTimer);
}

export const sleep = (millis: number) => new Promise<void>(resolve => setTimeout(resolve, millis));