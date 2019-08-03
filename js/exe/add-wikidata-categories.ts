import { startup, shutdown } from "../lib/promiseUtils";
import logger from "../lib/Logger";
import { WikidataImpl} from "../lib/mediawiki/Wikidata";
import { MediawikiImpl } from "../lib/mediawiki/MediawikiImpl";
const COMMONS_CATEGORY_PROPERTY = "P373";

startup();
(async() => {
    try {
        const wikidata = new WikidataImpl();
        const categoryMembers = await new MediawikiImpl({ threadPoolSize: 5 }).
            categoryMembersRecurse("Townships in Pennsylvania by county", 2);

        const bottomLevelCategories = <string[]>categoryMembers.map(member => member[member.length - 1]).map(member => {
            const baseName = member!.replace(/^Category:/, "");
            if (baseName !== member) {
                return baseName;
            }
        }).filter(member => member).slice(0, 50);

        const promises = [];

        for (const {id, sitelinks, claims = {}} of await wikidata.getentities("enwiki", bottomLevelCategories, ["sitelinks", "claims"])) {
            if (sitelinks) {
                const {enwiki, commonswiki} = sitelinks;
                if (enwiki) {
                    const {title} = enwiki;

                    if (!commonswiki) {
                        promises.push(wikidata.linktitles("commonswiki", `Category:${title}`, "enwiki", title).then(logger.debug));
                    }
                    if (!claims[COMMONS_CATEGORY_PROPERTY]) {
                        promises.push(wikidata.createclaim(id, COMMONS_CATEGORY_PROPERTY, title).then(logger.debug));
                    }
                }
            }
        }

        await Promise.all(promises);
    } catch (e) {
        logger.error(e);
    } finally {
        shutdown();
    }
})();