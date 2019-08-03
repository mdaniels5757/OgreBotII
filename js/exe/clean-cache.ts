import { ThreadPoolImpl } from "../lib/ThreadPool";
import { MediawikiImpl } from "../lib/mediawiki/MediawikiImpl";

const [category, numberOfThreads = 45] =  process.argv.slice(2);

if (!category) {
    throw new Error("Category name required");
}

const mw = new MediawikiImpl();
(async function () {
    let i = 0;
    do {
        var members = await mw.categoryMembers(category);
            
        await new ThreadPoolImpl(+numberOfThreads).enqueueAll(function* () {
            for (const member of members) {
                yield async() => {
                    console.log(`Purging #${++i}`);
                    await mw.editAppend(member, "", "\n\n");
                };
            }
        });
    } while (members.length > 500);
}());