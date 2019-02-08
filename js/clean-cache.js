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
const mw = mediawiki_1.default();
(async function () {
    let i = 0;
    do {
        const multithread = new multithreaded_promise_1.default(45);
        var members = await mw.categoryMembers(category);
        for (const member of members) {
            multithread.enqueue(async () => {
                if (member) {
                    console.log(`Purging #${++i}`);
                    return await mw.editAppend(member, "", "\n\n");
                }
            });
        }
        await multithread.done();
    } while (members.length > 500);
}());
//# sourceMappingURL=clean-cache.js.map