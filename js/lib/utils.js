"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
function* matchAll(regexp, string) {
    let match;
    var regexpClone = new RegExp(regexp.source, (regexp.flags + "g").replace(/(g.*)g$/, "$1"));
    while (match = regexpClone.exec(string)) {
        yield match;
    }
}
exports.matchAll = matchAll;
function sortCaseInsensitive(a, b) {
    return a.toLowerCase().localeCompare(b.toLowerCase());
}
exports.sortCaseInsensitive = sortCaseInsensitive;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoidXRpbHMuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJ1dGlscy50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOztBQUFBLFFBQWUsQ0FBQyxDQUFDLFFBQVEsQ0FBQyxNQUFjLEVBQUUsTUFBYztJQUNwRCxJQUFJLEtBQUssQ0FBQztJQUNWLElBQUksV0FBVyxHQUFHLElBQUksTUFBTSxDQUFDLE1BQU0sQ0FBQyxNQUFNLEVBQUUsQ0FBQyxNQUFNLENBQUMsS0FBSyxHQUFHLEdBQUcsQ0FBQyxDQUFDLE9BQU8sQ0FBQyxTQUFTLEVBQUUsSUFBSSxDQUFDLENBQUMsQ0FBQztJQUMzRixPQUFPLEtBQUssR0FBRyxXQUFXLENBQUMsSUFBSSxDQUFDLE1BQU0sQ0FBQyxFQUFFO1FBQ3JDLE1BQU0sS0FBSyxDQUFDO0tBQ2Y7QUFDTCxDQUFDO0FBTkQsNEJBTUM7QUFFRCxTQUFnQixtQkFBbUIsQ0FBQyxDQUFTLEVBQUUsQ0FBUztJQUNwRCxPQUFPLENBQUMsQ0FBQyxXQUFXLEVBQUUsQ0FBQyxhQUFhLENBQUMsQ0FBQyxDQUFDLFdBQVcsRUFBRSxDQUFDLENBQUM7QUFDMUQsQ0FBQztBQUZELGtEQUVDIn0=