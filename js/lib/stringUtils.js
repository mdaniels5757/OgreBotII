"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
function* matchAllPolyfill(regexp, string) {
    let match;
    var regexpClone = new RegExp(regexp);
    while (match = regexpClone.exec(string)) {
        yield match;
    }
}
exports.matchAll = RegExp.prototype.matchAll ? (regexp, string) => regexp.matchAll(string) : matchAllPolyfill;
function sortCaseInsensitive(a, b) {
    return a.toLowerCase().localeCompare(b.toLowerCase());
}
exports.sortCaseInsensitive = sortCaseInsensitive;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoic3RyaW5nVXRpbHMuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJzdHJpbmdVdGlscy50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOztBQU9BLFFBQVEsQ0FBQyxDQUFDLGdCQUFnQixDQUFFLE1BQWMsRUFBRSxNQUFjO0lBQ3RELElBQUksS0FBSyxDQUFDO0lBQ1YsSUFBSSxXQUFXLEdBQUcsSUFBSSxNQUFNLENBQUMsTUFBTSxDQUFDLENBQUM7SUFDckMsT0FBTyxLQUFLLEdBQUcsV0FBVyxDQUFDLElBQUksQ0FBQyxNQUFNLENBQUMsRUFBRTtRQUNyQyxNQUFNLEtBQUssQ0FBQztLQUNmO0FBQ0wsQ0FBQztBQUVZLFFBQUEsUUFBUSxHQUFHLE1BQU0sQ0FBQyxTQUFTLENBQUMsUUFBUSxDQUFDLENBQUMsQ0FBQyxDQUFDLE1BQWMsRUFBRSxNQUFjLEVBQUUsRUFBRSxDQUFDLE1BQU0sQ0FBQyxRQUFRLENBQUMsTUFBTSxDQUFDLENBQUMsQ0FBQyxDQUFDLGdCQUFnQixDQUFDO0FBRW5JLFNBQWdCLG1CQUFtQixDQUFDLENBQVMsRUFBRSxDQUFTO0lBQ3BELE9BQU8sQ0FBQyxDQUFDLFdBQVcsRUFBRSxDQUFDLGFBQWEsQ0FBQyxDQUFDLENBQUMsV0FBVyxFQUFFLENBQUMsQ0FBQztBQUMxRCxDQUFDO0FBRkQsa0RBRUMifQ==