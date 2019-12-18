export interface Store<K, V> {
    get(key: K): V | undefined;
    set(key: K, value: V): any;
}

export function computeIfAbsent<K, V>(store: Store<K, V>, key: K, valueFactory: () => V) {
    var value = store.get(key);
    if (value === undefined) {
        value = valueFactory();
        store.set(key, value);
    }
    return value;
}

export function arrayFindCallback<T>(array: T[], predicate: (value: T, index: number, obj: T[]) => boolean, thisArg?: any): number {
    const index = array.findIndex(predicate);
    if (index < 0) {
        throw new Error(`Predicate failed on array: ${JSON.stringify(array)}`);
    }
    return index;
}

export function arrayFind<T>(array: T[], value: T): number {
    return arrayFindCallback(array, e => e === value);
}

export function arrayFindAll<T>(array: T[], ...values: T[]): number[] {
    return values.map(value => arrayFind(array, value));
}