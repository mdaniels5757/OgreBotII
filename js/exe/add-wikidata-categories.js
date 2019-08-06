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
        const commons = await new MediawikiImpl_1.MediawikiImpl({ threadPoolSize: 5 });
        const categoryMembers = [
            ...(await commons.categoryMembersRecurse("Townships in Illinois‎", 2)),
        ];
        const bottomLevelCategories = categoryMembers.map(member => member[member.length - 1]).map(member => {
            const baseName = member.replace(/^Category:/, "");
            if (baseName !== member) {
                return baseName;
            }
        }).filter(member => member);
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
                        //set this action this on a timeout so as to avoid edit conflicts
                        promises.push(promiseUtils_1.sleep(5000).then(() => wikidata.createclaim(id, COMMONS_CATEGORY_PROPERTY, title).then(Logger_1.default.debug)));
                    }
                }
            }
        }
        Logger_1.default.info(`${promises.length} promsies queued.`);
        await Promise.all(promises);
    }
    catch (e) {
        Logger_1.default.error(e);
    }
    finally {
        promiseUtils_1.shutdown();
    }
})();
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiYWRkLXdpa2lkYXRhLWNhdGVnb3JpZXMuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJhZGQtd2lraWRhdGEtY2F0ZWdvcmllcy50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7OztBQUFBLHNEQUErRDtBQUMvRCwyREFBbUM7QUFDbkMsd0RBQXdEO0FBQ3hELGtFQUErRDtBQUMvRCxNQUFNLHlCQUF5QixHQUFHLE1BQU0sQ0FBQztBQUV6QyxzQkFBTyxFQUFFLENBQUM7QUFDVixDQUFDLEtBQUssSUFBRyxFQUFFO0lBQ1AsSUFBSTtRQUNBLE1BQU0sUUFBUSxHQUFHLElBQUksdUJBQVksRUFBRSxDQUFDO1FBQ3BDLE1BQU0sT0FBTyxHQUFHLE1BQU0sSUFBSSw2QkFBYSxDQUFDLEVBQUUsY0FBYyxFQUFFLENBQUMsRUFBRSxDQUFDLENBQUM7UUFDL0QsTUFBTSxlQUFlLEdBQUc7WUFDcEIsR0FBRyxDQUFDLE1BQU0sT0FBTyxDQUFDLHNCQUFzQixDQUFDLHdCQUF3QixFQUFFLENBQUMsQ0FBQyxDQUFDO1NBQ3JFLENBQUM7UUFFTixNQUFNLHFCQUFxQixHQUFhLGVBQWUsQ0FBQyxHQUFHLENBQUMsTUFBTSxDQUFDLEVBQUUsQ0FBQyxNQUFNLENBQUMsTUFBTSxDQUFDLE1BQU0sR0FBRyxDQUFDLENBQUMsQ0FBQyxDQUFDLEdBQUcsQ0FBQyxNQUFNLENBQUMsRUFBRTtZQUMxRyxNQUFNLFFBQVEsR0FBRyxNQUFPLENBQUMsT0FBTyxDQUFDLFlBQVksRUFBRSxFQUFFLENBQUMsQ0FBQztZQUNuRCxJQUFJLFFBQVEsS0FBSyxNQUFNLEVBQUU7Z0JBQ3JCLE9BQU8sUUFBUSxDQUFDO2FBQ25CO1FBQ0wsQ0FBQyxDQUFDLENBQUMsTUFBTSxDQUFDLE1BQU0sQ0FBQyxFQUFFLENBQUMsTUFBTSxDQUFDLENBQUM7UUFFNUIsTUFBTSxRQUFRLEdBQUcsRUFBRSxDQUFDO1FBRXBCLEtBQUssTUFBTSxFQUFDLEVBQUUsRUFBRSxTQUFTLEVBQUUsTUFBTSxHQUFHLEVBQUUsRUFBQyxJQUFJLE1BQU0sUUFBUSxDQUFDLFdBQVcsQ0FBQyxRQUFRLEVBQUUscUJBQXFCLEVBQUUsQ0FBQyxXQUFXLEVBQUUsUUFBUSxDQUFDLENBQUMsRUFBRTtZQUM3SCxJQUFJLFNBQVMsRUFBRTtnQkFDWCxNQUFNLEVBQUMsTUFBTSxFQUFFLFdBQVcsRUFBQyxHQUFHLFNBQVMsQ0FBQztnQkFDeEMsSUFBSSxNQUFNLEVBQUU7b0JBQ1IsTUFBTSxFQUFDLEtBQUssRUFBQyxHQUFHLE1BQU0sQ0FBQztvQkFFdkIsSUFBSSxDQUFDLFdBQVcsRUFBRTt3QkFDZCxRQUFRLENBQUMsSUFBSSxDQUFDLFFBQVEsQ0FBQyxVQUFVLENBQUMsYUFBYSxFQUFFLFlBQVksS0FBSyxFQUFFLEVBQUUsUUFBUSxFQUFFLEtBQUssQ0FBQyxDQUFDLElBQUksQ0FBQyxnQkFBTSxDQUFDLEtBQUssQ0FBQyxDQUFDLENBQUM7cUJBQzlHO29CQUNELElBQUksQ0FBQyxNQUFNLENBQUMseUJBQXlCLENBQUMsRUFBRTt3QkFDcEMsaUVBQWlFO3dCQUNqRSxRQUFRLENBQUMsSUFBSSxDQUFDLG9CQUFLLENBQUMsSUFBSSxDQUFDLENBQUMsSUFBSSxDQUFDLEdBQUcsRUFBRSxDQUFDLFFBQVEsQ0FBQyxXQUFXLENBQUMsRUFBRSxFQUFFLHlCQUF5QixFQUFFLEtBQUssQ0FBQyxDQUFDLElBQUksQ0FBQyxnQkFBTSxDQUFDLEtBQUssQ0FBQyxDQUFDLENBQUMsQ0FBQztxQkFDeEg7aUJBQ0o7YUFDSjtTQUNKO1FBQ0QsZ0JBQU0sQ0FBQyxJQUFJLENBQUMsR0FBRyxRQUFRLENBQUMsTUFBTSxtQkFBbUIsQ0FBQyxDQUFDO1FBQ25ELE1BQU0sT0FBTyxDQUFDLEdBQUcsQ0FBQyxRQUFRLENBQUMsQ0FBQztLQUMvQjtJQUFDLE9BQU8sQ0FBQyxFQUFFO1FBQ1IsZ0JBQU0sQ0FBQyxLQUFLLENBQUMsQ0FBQyxDQUFDLENBQUM7S0FDbkI7WUFBUztRQUNOLHVCQUFRLEVBQUUsQ0FBQztLQUNkO0FBQ0wsQ0FBQyxDQUFDLEVBQUUsQ0FBQyJ9