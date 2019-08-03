import { computeIfAbsent, Store } from "../collectionUtils";


type CacheableFactory<T>  = (wrapper: T, property: string) => Store<IArguments, any>
class MapBasedCache {
    private map : {[s: string]: any} = {};

    get(args: IArguments) {
        return this.map[this.serialize(args)];
    }

    set(args: IArguments, value: any) {
        return this.map[this.serialize(args)] = value;
    }

    protected serialize(args: IArguments) {
        return JSON.stringify(args);
    }
}

export const mapBasedCacheFactory = () => new MapBasedCache();

export const cachable = <T>(cacheFactory : CacheableFactory<T> = mapBasedCacheFactory) => {
    return (wrapper: T, property: string, propertyDescriptor: PropertyDescriptor) => {
        const callable = propertyDescriptor.value;

        //attached to this prototype and function, but should be unique for new object
        const allCaches = new Map<any, Store<IArguments, any>>();
        propertyDescriptor.value = function (this: T) {
            const cache = computeIfAbsent(allCaches, this, () => cacheFactory(wrapper, property));
            return computeIfAbsent(cache, arguments, () => callable.apply(this, arguments));
        };
    };
}