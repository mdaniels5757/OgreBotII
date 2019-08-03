"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const promiseUtils_1 = require("../lib/promiseUtils");
const Logger_1 = __importDefault(require("../lib/Logger"));
const Wikidata_1 = require("../lib/mediawiki/Wikidata");
const MediawikiImpl_1 = require("../lib/mediawiki/MediawikiImpl");
const COMMONS_CATEGORY_PROPERTY = "P373";
promiseUtils_1.startup();
(async () => {
    try {
        const wikidata = new Wikidata_1.WikidataImpl();
        const categoryMembers = await new MediawikiImpl_1.MediawikiImpl({ threadPoolSize: 5 }).
            categoryMembersRecurse("Townships in Pennsylvania by county", 2);
        const bottomLevelCategories = categoryMembers.map(member => member[member.length - 1]).map(member => {
            const baseName = member.replace(/^Category:/, "");
            if (baseName !== member) {
                return baseName;
            }
        }).filter(member => member).slice(0, 50);
        const promises = [];
        for (const { id, sitelinks, claims = {} } of await wikidata.getentities("enwiki", bottomLevelCategories, ["sitelinks", "claims"])) {
            if (sitelinks) {
                const { enwiki, commonswiki } = sitelinks;
                if (enwiki) {
                    const { title } = enwiki;
                    if (!commonswiki) {
                        promises.push(wikidata.linktitles("commonswiki", `Category:${title}`, "enwiki", title).then(Logger_1.default.debug));
                    }
                    if (!claims[COMMONS_CATEGORY_PROPERTY]) {
                        promises.push(wikidata.createclaim(id, COMMONS_CATEGORY_PROPERTY, title).then(Logger_1.default.debug));
                    }
                }
            }
        }
        await Promise.all(promises);
    }
    catch (e) {
        Logger_1.default.error(e);
    }
    finally {
        promiseUtils_1.shutdown();
    }
})();
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiYWRkLXdpa2lkYXRhLWNhdGVnb3JpZXMuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJhZGQtd2lraWRhdGEtY2F0ZWdvcmllcy50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7OztBQUFBLHNEQUF3RDtBQUN4RCwyREFBbUM7QUFDbkMsd0RBQXdEO0FBQ3hELGtFQUErRDtBQUMvRCxNQUFNLHlCQUF5QixHQUFHLE1BQU0sQ0FBQztBQUV6QyxzQkFBTyxFQUFFLENBQUM7QUFDVixDQUFDLEtBQUssSUFBRyxFQUFFO0lBQ1AsSUFBSTtRQUNBLE1BQU0sUUFBUSxHQUFHLElBQUksdUJBQVksRUFBRSxDQUFDO1FBQ3BDLE1BQU0sZUFBZSxHQUFHLE1BQU0sSUFBSSw2QkFBYSxDQUFDLEVBQUUsY0FBYyxFQUFFLENBQUMsRUFBRSxDQUFDO1lBQ2xFLHNCQUFzQixDQUFDLHFDQUFxQyxFQUFFLENBQUMsQ0FBQyxDQUFDO1FBRXJFLE1BQU0scUJBQXFCLEdBQWEsZUFBZSxDQUFDLEdBQUcsQ0FBQyxNQUFNLENBQUMsRUFBRSxDQUFDLE1BQU0sQ0FBQyxNQUFNLENBQUMsTUFBTSxHQUFHLENBQUMsQ0FBQyxDQUFDLENBQUMsR0FBRyxDQUFDLE1BQU0sQ0FBQyxFQUFFO1lBQzFHLE1BQU0sUUFBUSxHQUFHLE1BQU8sQ0FBQyxPQUFPLENBQUMsWUFBWSxFQUFFLEVBQUUsQ0FBQyxDQUFDO1lBQ25ELElBQUksUUFBUSxLQUFLLE1BQU0sRUFBRTtnQkFDckIsT0FBTyxRQUFRLENBQUM7YUFDbkI7UUFDTCxDQUFDLENBQUMsQ0FBQyxNQUFNLENBQUMsTUFBTSxDQUFDLEVBQUUsQ0FBQyxNQUFNLENBQUMsQ0FBQyxLQUFLLENBQUMsQ0FBQyxFQUFFLEVBQUUsQ0FBQyxDQUFDO1FBRXpDLE1BQU0sUUFBUSxHQUFHLEVBQUUsQ0FBQztRQUVwQixLQUFLLE1BQU0sRUFBQyxFQUFFLEVBQUUsU0FBUyxFQUFFLE1BQU0sR0FBRyxFQUFFLEVBQUMsSUFBSSxNQUFNLFFBQVEsQ0FBQyxXQUFXLENBQUMsUUFBUSxFQUFFLHFCQUFxQixFQUFFLENBQUMsV0FBVyxFQUFFLFFBQVEsQ0FBQyxDQUFDLEVBQUU7WUFDN0gsSUFBSSxTQUFTLEVBQUU7Z0JBQ1gsTUFBTSxFQUFDLE1BQU0sRUFBRSxXQUFXLEVBQUMsR0FBRyxTQUFTLENBQUM7Z0JBQ3hDLElBQUksTUFBTSxFQUFFO29CQUNSLE1BQU0sRUFBQyxLQUFLLEVBQUMsR0FBRyxNQUFNLENBQUM7b0JBRXZCLElBQUksQ0FBQyxXQUFXLEVBQUU7d0JBQ2QsUUFBUSxDQUFDLElBQUksQ0FBQyxRQUFRLENBQUMsVUFBVSxDQUFDLGFBQWEsRUFBRSxZQUFZLEtBQUssRUFBRSxFQUFFLFFBQVEsRUFBRSxLQUFLLENBQUMsQ0FBQyxJQUFJLENBQUMsZ0JBQU0sQ0FBQyxLQUFLLENBQUMsQ0FBQyxDQUFDO3FCQUM5RztvQkFDRCxJQUFJLENBQUMsTUFBTSxDQUFDLHlCQUF5QixDQUFDLEVBQUU7d0JBQ3BDLFFBQVEsQ0FBQyxJQUFJLENBQUMsUUFBUSxDQUFDLFdBQVcsQ0FBQyxFQUFFLEVBQUUseUJBQXlCLEVBQUUsS0FBSyxDQUFDLENBQUMsSUFBSSxDQUFDLGdCQUFNLENBQUMsS0FBSyxDQUFDLENBQUMsQ0FBQztxQkFDaEc7aUJBQ0o7YUFDSjtTQUNKO1FBRUQsTUFBTSxPQUFPLENBQUMsR0FBRyxDQUFDLFFBQVEsQ0FBQyxDQUFDO0tBQy9CO0lBQUMsT0FBTyxDQUFDLEVBQUU7UUFDUixnQkFBTSxDQUFDLEtBQUssQ0FBQyxDQUFDLENBQUMsQ0FBQztLQUNuQjtZQUFTO1FBQ04sdUJBQVEsRUFBRSxDQUFDO0tBQ2Q7QUFDTCxDQUFDLENBQUMsRUFBRSxDQUFDIn0=