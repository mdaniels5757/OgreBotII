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
exports.sleep = (millis) => new Promise(resolve => setTimeout(resolve, millis));
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoicHJvbWlzZVV0aWxzLmpzIiwic291cmNlUm9vdCI6IiIsInNvdXJjZXMiOlsicHJvbWlzZVV0aWxzLnRzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiI7O0FBQUEsSUFBSSxZQUE2QixDQUFDO0FBQ2xDLE1BQU0sV0FBVyxHQUFHLFVBQVUsQ0FBQztBQUUvQiw4Q0FBOEM7QUFDOUMsU0FBZ0IsT0FBTyxDQUFDLE9BQU8sR0FBRyxXQUFXO0lBQ3pDLFlBQVksR0FBRyxZQUFZLElBQUksVUFBVSxDQUFDLEdBQUcsRUFBRSxHQUFFLENBQUMsRUFBRSxPQUFPLENBQUMsQ0FBQztBQUNqRSxDQUFDO0FBRkQsMEJBRUM7QUFFRCxTQUFnQixRQUFRO0lBQ3BCLFlBQVksSUFBSSxZQUFZLENBQUMsWUFBWSxDQUFDLENBQUM7QUFDL0MsQ0FBQztBQUZELDRCQUVDO0FBRVksUUFBQSxLQUFLLEdBQUcsQ0FBQyxNQUFjLEVBQUUsRUFBRSxDQUFDLElBQUksT0FBTyxDQUFPLE9BQU8sQ0FBQyxFQUFFLENBQUMsVUFBVSxDQUFDLE9BQU8sRUFBRSxNQUFNLENBQUMsQ0FBQyxDQUFDIn0=