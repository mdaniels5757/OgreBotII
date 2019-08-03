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