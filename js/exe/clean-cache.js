"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const ThreadPool_1 = require("../lib/ThreadPool");
const MediawikiImpl_1 = require("../lib/mediawiki/MediawikiImpl");
const [category, numberOfThreads = 45] = process.argv.slice(2);
if (!category) {
    throw new Error("Category name required");
}
const mw = new MediawikiImpl_1.MediawikiImpl();
(async function () {
    let i = 0;
    do {
        var members = await mw.categoryMembers(category);
        await new ThreadPool_1.ThreadPoolImpl(+numberOfThreads).enqueueAll(function* () {
            for (const member of members) {
                yield async () => {
                    console.log(`Purging #${++i}`);
                    await mw.editAppend(member, "", "\n\n");
                };
            }
        });
    } while (members.length > 500);
}());
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiY2xlYW4tY2FjaGUuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJjbGVhbi1jYWNoZS50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOztBQUFBLGtEQUFtRDtBQUNuRCxrRUFBK0Q7QUFFL0QsTUFBTSxDQUFDLFFBQVEsRUFBRSxlQUFlLEdBQUcsRUFBRSxDQUFDLEdBQUksT0FBTyxDQUFDLElBQUksQ0FBQyxLQUFLLENBQUMsQ0FBQyxDQUFDLENBQUM7QUFFaEUsSUFBSSxDQUFDLFFBQVEsRUFBRTtJQUNYLE1BQU0sSUFBSSxLQUFLLENBQUMsd0JBQXdCLENBQUMsQ0FBQztDQUM3QztBQUVELE1BQU0sRUFBRSxHQUFHLElBQUksNkJBQWEsRUFBRSxDQUFDO0FBQy9CLENBQUMsS0FBSztJQUNGLElBQUksQ0FBQyxHQUFHLENBQUMsQ0FBQztJQUNWLEdBQUc7UUFDQyxJQUFJLE9BQU8sR0FBRyxNQUFNLEVBQUUsQ0FBQyxlQUFlLENBQUMsUUFBUSxDQUFDLENBQUM7UUFFakQsTUFBTSxJQUFJLDJCQUFjLENBQUMsQ0FBQyxlQUFlLENBQUMsQ0FBQyxVQUFVLENBQUMsUUFBUSxDQUFDO1lBQzNELEtBQUssTUFBTSxNQUFNLElBQUksT0FBTyxFQUFFO2dCQUMxQixNQUFNLEtBQUssSUFBRyxFQUFFO29CQUNaLE9BQU8sQ0FBQyxHQUFHLENBQUMsWUFBWSxFQUFFLENBQUMsRUFBRSxDQUFDLENBQUM7b0JBQy9CLE1BQU0sRUFBRSxDQUFDLFVBQVUsQ0FBQyxNQUFNLEVBQUUsRUFBRSxFQUFFLE1BQU0sQ0FBQyxDQUFDO2dCQUM1QyxDQUFDLENBQUM7YUFDTDtRQUNMLENBQUMsQ0FBQyxDQUFDO0tBQ04sUUFBUSxPQUFPLENBQUMsTUFBTSxHQUFHLEdBQUcsRUFBRTtBQUNuQyxDQUFDLEVBQUUsQ0FBQyxDQUFDIn0=