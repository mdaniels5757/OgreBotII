
import getMediawiki from "./mediawiki";
import MultiThreadedPromise from "./multithreaded-promise";

const category = process.argv[2];
if (!category) {
    throw new Error("Category name required");
}

const mw = getMediawiki();
(async function () {
    let i = 0;
    do {
        const multithread = new MultiThreadedPromise(45);
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