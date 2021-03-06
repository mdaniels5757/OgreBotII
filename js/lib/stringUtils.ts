declare global {
    interface RegExp {
        matchAll(string: string): IterableIterator<RegExpExecArray>;
    }
}


function* matchAllPolyfill (regexp: RegExp, string: string) {
    let match;
    var regexpClone = new RegExp(regexp);
    while (match = regexpClone.exec(string)) {
        yield match;
    }
}

export const matchAll = RegExp.prototype.matchAll ? (regexp: RegExp, string: string) => regexp.matchAll(string) : matchAllPolyfill;

export function sortCaseInsensitive(a: String, b: String) {
    return a.toLowerCase().localeCompare(b.toLowerCase());
}

export function stringHash(input: any) {
    return String(input).split('').reduce((prevHash, currVal) =>
      (((prevHash << 5) - prevHash) + currVal.charCodeAt(0))|0, 0);
}