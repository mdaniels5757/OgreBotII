"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
const AbstractMediawiki_1 = require("./AbstractMediawiki");
const Mediawiki_1 = require("./Mediawiki");
const awaitReady_1 = require("../decorators/awaitReady");
const cachable_1 = require("../decorators/cachable");
class WikidataImpl extends AbstractMediawiki_1.AbstractMediawiki {
    constructor() {
        super({ username: Mediawiki_1.MediawikiUsername.OGREBOT, api: Mediawiki_1.MediawikiApi.WIKIDATA, threadPoolSize: 1, throttle: 10000 });
    }
    async linktitles(tosite, totitle, fromsite, fromtitle) {
        const { success } = await this.post({
            action: "wblinktitles",
            bot: true,
            token: await this.fetchToken("csrf"),
            tosite, totitle, fromtitle, fromsite
        }, true);
        return !!success;
    }
    async createclaim(entity, property, value, snaktype = "value") {
        const { success } = await this.post({
            action: "wbcreateclaim",
            entity,
            property,
            snaktype,
            value: JSON.stringify(value),
            bot: true,
            token: await this.fetchToken("csrf")
        }, true);
        return !!success;
    }
    async getentities(site, titles, props) {
        const { entities, success } = await this.post({
            action: "wbgetentities",
            sites: site,
            titles: titles.join("|"),
            props: props.join("|"),
            bot: true
        }, false);
        if (!success) {
            throw new Error(`Unsuccessful response.`);
        }
        return Object.values(entities).filter(entity => entity.missing === undefined);
    }
}
__decorate([
    awaitReady_1.awaitReady()
], WikidataImpl.prototype, "linktitles", null);
__decorate([
    awaitReady_1.awaitReady()
], WikidataImpl.prototype, "createclaim", null);
__decorate([
    cachable_1.cachable(),
    awaitReady_1.awaitReady()
], WikidataImpl.prototype, "getentities", null);
exports.WikidataImpl = WikidataImpl;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiV2lraWRhdGEuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJXaWtpZGF0YS50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7Ozs7OztBQUFBLDJEQUF3RDtBQUN4RCwyQ0FBOEQ7QUFDOUQseURBQXNEO0FBQ3RELHFEQUFrRDtBQThDbEQsTUFBYSxZQUFhLFNBQVEscUNBQWlCO0lBRS9DO1FBQ0ksS0FBSyxDQUFDLEVBQUMsUUFBUSxFQUFFLDZCQUFpQixDQUFDLE9BQU8sRUFBRSxHQUFHLEVBQUUsd0JBQVksQ0FBQyxRQUFRLEVBQUUsY0FBYyxFQUFFLENBQUMsRUFBRSxRQUFRLEVBQUUsS0FBSyxFQUFDLENBQUMsQ0FBQTtJQUNoSCxDQUFDO0lBR0QsS0FBSyxDQUFDLFVBQVUsQ0FBQyxNQUFjLEVBQUUsT0FBZSxFQUFFLFFBQWdCLEVBQUUsU0FBaUI7UUFDakYsTUFBTSxFQUFDLE9BQU8sRUFBQyxHQUFHLE1BQU0sSUFBSSxDQUFDLElBQUksQ0FBQztZQUM5QixNQUFNLEVBQUUsY0FBYztZQUN0QixHQUFHLEVBQUUsSUFBSTtZQUNULEtBQUssRUFBRSxNQUFNLElBQUksQ0FBQyxVQUFVLENBQUMsTUFBTSxDQUFDO1lBQ3BDLE1BQU0sRUFBRSxPQUFPLEVBQUUsU0FBUyxFQUFFLFFBQVE7U0FDdkMsRUFBRSxJQUFJLENBQUMsQ0FBQztRQUNULE9BQU8sQ0FBQyxDQUFDLE9BQU8sQ0FBQztJQUNyQixDQUFDO0lBR0QsS0FBSyxDQUFDLFdBQVcsQ0FBQyxNQUFjLEVBQUUsUUFBZ0IsRUFBRSxLQUFVLEVBQUUsV0FBb0IsT0FBTztRQUN2RixNQUFNLEVBQUMsT0FBTyxFQUFDLEdBQUcsTUFBTSxJQUFJLENBQUMsSUFBSSxDQUFDO1lBQzlCLE1BQU0sRUFBRSxlQUFlO1lBQ3ZCLE1BQU07WUFDTixRQUFRO1lBQ1IsUUFBUTtZQUNSLEtBQUssRUFBRSxJQUFJLENBQUMsU0FBUyxDQUFDLEtBQUssQ0FBQztZQUM1QixHQUFHLEVBQUUsSUFBSTtZQUNULEtBQUssRUFBRSxNQUFNLElBQUksQ0FBQyxVQUFVLENBQUMsTUFBTSxDQUFDO1NBQ3ZDLEVBQUUsSUFBSSxDQUFDLENBQUM7UUFDVCxPQUFPLENBQUMsQ0FBQyxPQUFPLENBQUM7SUFDckIsQ0FBQztJQUlELEtBQUssQ0FBQyxXQUFXLENBQUMsSUFBWSxFQUFFLE1BQWdCLEVBQUUsS0FBZTtRQUM3RCxNQUFNLEVBQUMsUUFBUSxFQUFFLE9BQU8sRUFBQyxHQUFtQixNQUFNLElBQUksQ0FBQyxJQUFJLENBQUM7WUFDeEQsTUFBTSxFQUFFLGVBQWU7WUFDdkIsS0FBSyxFQUFFLElBQUk7WUFDWCxNQUFNLEVBQUUsTUFBTSxDQUFDLElBQUksQ0FBQyxHQUFHLENBQUM7WUFDeEIsS0FBSyxFQUFFLEtBQUssQ0FBQyxJQUFJLENBQUMsR0FBRyxDQUFDO1lBQ3RCLEdBQUcsRUFBRSxJQUFJO1NBQ1osRUFBRSxLQUFLLENBQUMsQ0FBQztRQUNWLElBQUksQ0FBQyxPQUFPLEVBQUU7WUFDVixNQUFNLElBQUksS0FBSyxDQUFDLHdCQUF3QixDQUFDLENBQUM7U0FDN0M7UUFDRCxPQUFPLE1BQU0sQ0FBQyxNQUFNLENBQUMsUUFBUSxDQUFDLENBQUMsTUFBTSxDQUFDLE1BQU0sQ0FBQyxFQUFFLENBQUMsTUFBTSxDQUFDLE9BQU8sS0FBSyxTQUFTLENBQUMsQ0FBQztJQUNsRixDQUFDO0NBQ0o7QUF2Q0c7SUFEQyx1QkFBVSxFQUFFOzhDQVNaO0FBR0Q7SUFEQyx1QkFBVSxFQUFFOytDQVlaO0FBSUQ7SUFGQyxtQkFBUSxFQUFFO0lBQ1YsdUJBQVUsRUFBRTsrQ0FhWjtBQTdDTCxvQ0E4Q0MifQ==