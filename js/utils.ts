export function* matchAll(regexFactory: () => RegExp, string: string) {
    const regexp = regexFactory();
    let match;
    while (match = regexp.exec(string)) {
        yield match;
    }
}