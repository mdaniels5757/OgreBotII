"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
function computeIfAbsent(store, key, valueFactory) {
    var value = store.get(key);
    if (value === undefined) {
        value = valueFactory();
        store.set(key, value);
    }
    return value;
}
exports.computeIfAbsent = computeIfAbsent;
function arrayFindCallback(array, predicate, thisArg) {
    const index = array.findIndex(predicate);
    if (index < 0) {
        throw new Error(`Predicate failed on array: ${JSON.stringify(array)}`);
    }
    return index;
}
exports.arrayFindCallback = arrayFindCallback;
function arrayFind(array, value) {
    return arrayFindCallback(array, e => e === value);
}
exports.arrayFind = arrayFind;
function arrayFindAll(array, ...values) {
    return values.map(value => arrayFind(array, value));
}
exports.arrayFindAll = arrayFindAll;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiY29sbGVjdGlvblV0aWxzLmpzIiwic291cmNlUm9vdCI6IiIsInNvdXJjZXMiOlsiY29sbGVjdGlvblV0aWxzLnRzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiI7O0FBS0EsU0FBZ0IsZUFBZSxDQUFPLEtBQWtCLEVBQUUsR0FBTSxFQUFFLFlBQXFCO0lBQ25GLElBQUksS0FBSyxHQUFHLEtBQUssQ0FBQyxHQUFHLENBQUMsR0FBRyxDQUFDLENBQUM7SUFDM0IsSUFBSSxLQUFLLEtBQUssU0FBUyxFQUFFO1FBQ3JCLEtBQUssR0FBRyxZQUFZLEVBQUUsQ0FBQztRQUN2QixLQUFLLENBQUMsR0FBRyxDQUFDLEdBQUcsRUFBRSxLQUFLLENBQUMsQ0FBQztLQUN6QjtJQUNELE9BQU8sS0FBSyxDQUFDO0FBQ2pCLENBQUM7QUFQRCwwQ0FPQztBQUVELFNBQWdCLGlCQUFpQixDQUFJLEtBQVUsRUFBRSxTQUF5RCxFQUFFLE9BQWE7SUFDckgsTUFBTSxLQUFLLEdBQUcsS0FBSyxDQUFDLFNBQVMsQ0FBQyxTQUFTLENBQUMsQ0FBQztJQUN6QyxJQUFJLEtBQUssR0FBRyxDQUFDLEVBQUU7UUFDWCxNQUFNLElBQUksS0FBSyxDQUFDLDhCQUE4QixJQUFJLENBQUMsU0FBUyxDQUFDLEtBQUssQ0FBQyxFQUFFLENBQUMsQ0FBQztLQUMxRTtJQUNELE9BQU8sS0FBSyxDQUFDO0FBQ2pCLENBQUM7QUFORCw4Q0FNQztBQUVELFNBQWdCLFNBQVMsQ0FBSSxLQUFVLEVBQUUsS0FBUTtJQUM3QyxPQUFPLGlCQUFpQixDQUFDLEtBQUssRUFBRSxDQUFDLENBQUMsRUFBRSxDQUFDLENBQUMsS0FBSyxLQUFLLENBQUMsQ0FBQztBQUN0RCxDQUFDO0FBRkQsOEJBRUM7QUFFRCxTQUFnQixZQUFZLENBQUksS0FBVSxFQUFFLEdBQUcsTUFBVztJQUN0RCxPQUFPLE1BQU0sQ0FBQyxHQUFHLENBQUMsS0FBSyxDQUFDLEVBQUUsQ0FBQyxTQUFTLENBQUMsS0FBSyxFQUFFLEtBQUssQ0FBQyxDQUFDLENBQUM7QUFDeEQsQ0FBQztBQUZELG9DQUVDIn0=