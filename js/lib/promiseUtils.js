"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var warningTimer;
const MAX_TIMEOUT = 0x7fffffff;
//dummy timeout to keep the Node process alive
function startup(timeout = MAX_TIMEOUT) {
    warningTimer = warningTimer || setTimeout(() => { }, timeout);
}
exports.startup = startup;
function shutdown() {
    warningTimer && clearTimeout(warningTimer);
}
exports.shutdown = shutdown;
class SleepPromise {
    constructor(millis) {
        this._timeout = 0;
        this._promise = new Promise(resolve => {
            this._timeout = setTimeout(resolve, millis);
        });
    }
    get promise() {
        return this._promise;
    }
    cancel() {
        clearTimeout(this._timeout);
    }
}
exports.SleepPromise = SleepPromise;
exports.sleep = (millis) => new SleepPromise(millis).promise;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoicHJvbWlzZVV0aWxzLmpzIiwic291cmNlUm9vdCI6IiIsInNvdXJjZXMiOlsicHJvbWlzZVV0aWxzLnRzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiI7O0FBQUEsSUFBSSxZQUE2QixDQUFDO0FBQ2xDLE1BQU0sV0FBVyxHQUFHLFVBQVUsQ0FBQztBQUUvQiw4Q0FBOEM7QUFDOUMsU0FBZ0IsT0FBTyxDQUFDLE9BQU8sR0FBRyxXQUFXO0lBQ3pDLFlBQVksR0FBRyxZQUFZLElBQUksVUFBVSxDQUFDLEdBQUcsRUFBRSxHQUFFLENBQUMsRUFBRSxPQUFPLENBQUMsQ0FBQztBQUNqRSxDQUFDO0FBRkQsMEJBRUM7QUFFRCxTQUFnQixRQUFRO0lBQ3BCLFlBQVksSUFBSSxZQUFZLENBQUMsWUFBWSxDQUFDLENBQUM7QUFDL0MsQ0FBQztBQUZELDRCQUVDO0FBRUQsTUFBYSxZQUFZO0lBSXJCLFlBQVksTUFBYztRQUhsQixhQUFRLEdBQVcsQ0FBQyxDQUFDO1FBSXpCLElBQUksQ0FBQyxRQUFRLEdBQUcsSUFBSSxPQUFPLENBQU8sT0FBTyxDQUFDLEVBQUU7WUFDeEMsSUFBSSxDQUFDLFFBQVEsR0FBRyxVQUFVLENBQUMsT0FBTyxFQUFFLE1BQU0sQ0FBQyxDQUFBO1FBQy9DLENBQUMsQ0FBQyxDQUFDO0lBQ1AsQ0FBQztJQUVELElBQVcsT0FBTztRQUNkLE9BQU8sSUFBSSxDQUFDLFFBQVEsQ0FBQztJQUN6QixDQUFDO0lBRU0sTUFBTTtRQUNULFlBQVksQ0FBQyxJQUFJLENBQUMsUUFBUSxDQUFDLENBQUM7SUFDaEMsQ0FBQztDQUNKO0FBakJELG9DQWlCQztBQUdZLFFBQUEsS0FBSyxHQUFHLENBQUMsTUFBYyxFQUFFLEVBQUUsQ0FBQyxJQUFJLFlBQVksQ0FBQyxNQUFNLENBQUMsQ0FBQyxPQUFPLENBQUMifQ==