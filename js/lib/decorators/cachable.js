"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const collectionUtils_1 = require("../collectionUtils");
class MapBasedCache {
    constructor() {
        this.map = {};
    }
    get(args) {
        return this.map[this.serialize(args)];
    }
    set(args, value) {
        return this.map[this.serialize(args)] = value;
    }
    serialize(args) {
        return JSON.stringify(args);
    }
}
exports.mapBasedCacheFactory = () => new MapBasedCache();
exports.cachable = (cacheFactory = exports.mapBasedCacheFactory) => {
    return (wrapper, property, propertyDescriptor) => {
        const callable = propertyDescriptor.value;
        //attached to this prototype and function, but should be unique for new object
        const allCaches = new Map();
        propertyDescriptor.value = function () {
            const cache = collectionUtils_1.computeIfAbsent(allCaches, this, () => cacheFactory(wrapper, property));
            return collectionUtils_1.computeIfAbsent(cache, arguments, () => callable.apply(this, arguments));
        };
    };
};
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiY2FjaGFibGUuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJjYWNoYWJsZS50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOztBQUFBLHdEQUE0RDtBQUk1RCxNQUFNLGFBQWE7SUFBbkI7UUFDWSxRQUFHLEdBQXdCLEVBQUUsQ0FBQztJQWExQyxDQUFDO0lBWEcsR0FBRyxDQUFDLElBQWdCO1FBQ2hCLE9BQU8sSUFBSSxDQUFDLEdBQUcsQ0FBQyxJQUFJLENBQUMsU0FBUyxDQUFDLElBQUksQ0FBQyxDQUFDLENBQUM7SUFDMUMsQ0FBQztJQUVELEdBQUcsQ0FBQyxJQUFnQixFQUFFLEtBQVU7UUFDNUIsT0FBTyxJQUFJLENBQUMsR0FBRyxDQUFDLElBQUksQ0FBQyxTQUFTLENBQUMsSUFBSSxDQUFDLENBQUMsR0FBRyxLQUFLLENBQUM7SUFDbEQsQ0FBQztJQUVTLFNBQVMsQ0FBQyxJQUFnQjtRQUNoQyxPQUFPLElBQUksQ0FBQyxTQUFTLENBQUMsSUFBSSxDQUFDLENBQUM7SUFDaEMsQ0FBQztDQUNKO0FBRVksUUFBQSxvQkFBb0IsR0FBRyxHQUFHLEVBQUUsQ0FBQyxJQUFJLGFBQWEsRUFBRSxDQUFDO0FBRWpELFFBQUEsUUFBUSxHQUFHLENBQUksZUFBcUMsNEJBQW9CLEVBQUUsRUFBRTtJQUNyRixPQUFPLENBQUMsT0FBVSxFQUFFLFFBQWdCLEVBQUUsa0JBQXNDLEVBQUUsRUFBRTtRQUM1RSxNQUFNLFFBQVEsR0FBRyxrQkFBa0IsQ0FBQyxLQUFLLENBQUM7UUFFMUMsOEVBQThFO1FBQzlFLE1BQU0sU0FBUyxHQUFHLElBQUksR0FBRyxFQUErQixDQUFDO1FBQ3pELGtCQUFrQixDQUFDLEtBQUssR0FBRztZQUN2QixNQUFNLEtBQUssR0FBRyxpQ0FBZSxDQUFDLFNBQVMsRUFBRSxJQUFJLEVBQUUsR0FBRyxFQUFFLENBQUMsWUFBWSxDQUFDLE9BQU8sRUFBRSxRQUFRLENBQUMsQ0FBQyxDQUFDO1lBQ3RGLE9BQU8saUNBQWUsQ0FBQyxLQUFLLEVBQUUsU0FBUyxFQUFFLEdBQUcsRUFBRSxDQUFDLFFBQVEsQ0FBQyxLQUFLLENBQUMsSUFBSSxFQUFFLFNBQVMsQ0FBQyxDQUFDLENBQUM7UUFDcEYsQ0FBQyxDQUFDO0lBQ04sQ0FBQyxDQUFDO0FBQ04sQ0FBQyxDQUFBIn0=