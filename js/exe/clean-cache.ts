
import getMediawiki from "../lib/mediawiki";
import MultiThreadedPromise from "../lib/multithreaded-promise";

const [category, numberOfThreads = 45] =  process.argv.slice(2);

if (!category) {
    throw new Error("Category name required");
}

const mw = getMediawiki();
(async function () {
    let i = 0;
    do {
        const multithread = new MultiThreadedPromise(+numberOfThreads);
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