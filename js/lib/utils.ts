export function* matchAll(regexp: RegExp, string: string) {
    let match;
    var regexpClone = new RegExp(regexp.source, (regexp.flags + "g").replace(/(g.*)g$/, "$1"));
    while (match = regexpClone.exec(string)) {
        yield match;
    }
}

export function sortCaseInsensitive(a: String, b: String) {
    return a.toLowerCase().localeCompare(b.toLowerCase());
}