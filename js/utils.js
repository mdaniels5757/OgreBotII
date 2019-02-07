"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
function* matchAll(regexFactory, string) {
    const regexp = regexFactory();
    let match;
    while (match = regexp.exec(string)) {
        yield match;
    }
}
exports.matchAll = matchAll;
//# sourceMappingURL=utils.js.map