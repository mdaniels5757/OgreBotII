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
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiY29sbGVjdGlvblV0aWxzLmpzIiwic291cmNlUm9vdCI6IiIsInNvdXJjZXMiOlsiY29sbGVjdGlvblV0aWxzLnRzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiI7O0FBS0EsU0FBZ0IsZUFBZSxDQUFPLEtBQWtCLEVBQUUsR0FBTSxFQUFFLFlBQXFCO0lBQ25GLElBQUksS0FBSyxHQUFHLEtBQUssQ0FBQyxHQUFHLENBQUMsR0FBRyxDQUFDLENBQUM7SUFDM0IsSUFBSSxLQUFLLEtBQUssU0FBUyxFQUFFO1FBQ3JCLEtBQUssR0FBRyxZQUFZLEVBQUUsQ0FBQztRQUN2QixLQUFLLENBQUMsR0FBRyxDQUFDLEdBQUcsRUFBRSxLQUFLLENBQUMsQ0FBQztLQUN6QjtJQUNELE9BQU8sS0FBSyxDQUFDO0FBQ2pCLENBQUM7QUFQRCwwQ0FPQyJ9