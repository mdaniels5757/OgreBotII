import { startup, shutdown, sleep } from "../lib/promiseUtils";
import logger from "../lib/Logger";
import { WikidataImpl} from "../lib/mediawiki/Wikidata";
import { MediawikiImpl } from "../lib/mediawiki/MediawikiImpl";
const COMMONS_CATEGORY_PROPERTY = "P373";

startup();
(async() => {
    try {
        const wikidata = new WikidataImpl();
        const commons = await new MediawikiImpl({ threadPoolSize: 5 });
        const categoryMembers = [
            ...(await commons.categoryMembersRecurse("Townships in Illinois‎", 2)), 
            ];

        const bottomLevelCategories = <string[]>categoryMembers.map(member => member[member.length - 1]).map(member => {
            const baseName = member!.replace(/^Category:/, "");
            if (baseName !== member) {
                return baseName;
            }
        }).filter(member => member);

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
                        //set this action this on a timeout so as to avoid edit conflicts
                        promises.push(sleep(5000).then(() => wikidata.createclaim(id, COMMONS_CATEGORY_PROPERTY, title).then(logger.debug)));
                    }
                }
            }
        }
        logger.info(`${promises.length} promsies queued.`);
        await Promise.all(promises);
    } catch (e) {
        logger.error(e);
    } finally {
        shutdown();
    }
})();