
import getMediawiki from "./mediawiki";
import MultiThreadedPromise from "./multithreaded-promise";

const category = process.argv[2];
if (!category) {
    throw new Error("Category name required");
}

(async function () {
    var mw = getMediawiki();
    var multithread = new MultiThreadedPromise();
    const members = await mw.categoryMembers(category);
    for (let i = 0; i < Math.min(5000, members.length); i++) {
        multithread.enqueue(() => {
            console.log(`Purging #${i}`);
            return mw.editAppend(members[i], "", "\n\n");
        });
    }
    await multithread.done();
}());