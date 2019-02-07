"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const mediawiki_1 = __importDefault(require("./mediawiki"));
const multithreaded_promise_1 = __importDefault(require("./multithreaded-promise"));
const category = process.argv[2];
if (!category) {
    throw new Error("Category name required");
}
(async function () {
    var mw = mediawiki_1.default();
    var multithread = new multithreaded_promise_1.default();
    const members = await mw.categoryMembers(category);
    for (let i = 0; i < Math.min(5000, members.length); i++) {
        multithread.enqueue(() => {
            console.log(`Purging #${i}`);
            return mw.editAppend(members[i], "", "\n\n");
        });
    }
    await multithread.done();
}());
//# sourceMappingURL=clean-cache.js.map