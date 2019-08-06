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
        super({ username: Mediawiki_1.MediawikiUsername.OGREBOT, api: Mediawiki_1.MediawikiApi.WIKIDATA, threadPoolSize: 2, throttle: 0 });
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
        const MAX_SIZE = 500;
        const allEntities = {};
        for (var i = 0; i < titles.length; i += MAX_SIZE) {
            const { entities, success } = await this.post({
                action: "wbgetentities",
                sites: site,
                titles: titles.slice(i, i + MAX_SIZE).join("|"),
                props: props.join("|"),
                bot: true
            }, false);
            if (!success) {
                throw new Error(`Unsuccessful response.`);
            }
            Object.assign(allEntities, entities);
        }
        return Object.values(allEntities).filter(entity => entity.missing === undefined);
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
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiV2lraWRhdGEuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJXaWtpZGF0YS50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7Ozs7OztBQUFBLDJEQUF3RDtBQUN4RCwyQ0FBOEQ7QUFDOUQseURBQXNEO0FBQ3RELHFEQUFrRDtBQThDbEQsTUFBYSxZQUFhLFNBQVEscUNBQWlCO0lBRS9DO1FBQ0ksS0FBSyxDQUFDLEVBQUMsUUFBUSxFQUFFLDZCQUFpQixDQUFDLE9BQU8sRUFBRSxHQUFHLEVBQUUsd0JBQVksQ0FBQyxRQUFRLEVBQUUsY0FBYyxFQUFFLENBQUMsRUFBRSxRQUFRLEVBQUUsQ0FBQyxFQUFDLENBQUMsQ0FBQTtJQUM1RyxDQUFDO0lBR0QsS0FBSyxDQUFDLFVBQVUsQ0FBQyxNQUFjLEVBQUUsT0FBZSxFQUFFLFFBQWdCLEVBQUUsU0FBaUI7UUFDakYsTUFBTSxFQUFDLE9BQU8sRUFBQyxHQUFHLE1BQU0sSUFBSSxDQUFDLElBQUksQ0FBQztZQUM5QixNQUFNLEVBQUUsY0FBYztZQUN0QixHQUFHLEVBQUUsSUFBSTtZQUNULEtBQUssRUFBRSxNQUFNLElBQUksQ0FBQyxVQUFVLENBQUMsTUFBTSxDQUFDO1lBQ3BDLE1BQU0sRUFBRSxPQUFPLEVBQUUsU0FBUyxFQUFFLFFBQVE7U0FDdkMsRUFBRSxJQUFJLENBQUMsQ0FBQztRQUNULE9BQU8sQ0FBQyxDQUFDLE9BQU8sQ0FBQztJQUNyQixDQUFDO0lBR0QsS0FBSyxDQUFDLFdBQVcsQ0FBQyxNQUFjLEVBQUUsUUFBZ0IsRUFBRSxLQUFVLEVBQUUsV0FBb0IsT0FBTztRQUN2RixNQUFNLEVBQUMsT0FBTyxFQUFDLEdBQUcsTUFBTSxJQUFJLENBQUMsSUFBSSxDQUFDO1lBQzlCLE1BQU0sRUFBRSxlQUFlO1lBQ3ZCLE1BQU07WUFDTixRQUFRO1lBQ1IsUUFBUTtZQUNSLEtBQUssRUFBRSxJQUFJLENBQUMsU0FBUyxDQUFDLEtBQUssQ0FBQztZQUM1QixHQUFHLEVBQUUsSUFBSTtZQUNULEtBQUssRUFBRSxNQUFNLElBQUksQ0FBQyxVQUFVLENBQUMsTUFBTSxDQUFDO1NBQ3ZDLEVBQUUsSUFBSSxDQUFDLENBQUM7UUFDVCxPQUFPLENBQUMsQ0FBQyxPQUFPLENBQUM7SUFDckIsQ0FBQztJQUlELEtBQUssQ0FBQyxXQUFXLENBQUMsSUFBWSxFQUFFLE1BQWdCLEVBQUUsS0FBZTtRQUM3RCxNQUFNLFFBQVEsR0FBRyxHQUFHLENBQUM7UUFDckIsTUFBTSxXQUFXLEdBQTJCLEVBQUUsQ0FBQztRQUMvQyxLQUFLLElBQUksQ0FBQyxHQUFHLENBQUMsRUFBRSxDQUFDLEdBQUcsTUFBTSxDQUFDLE1BQU0sRUFBRSxDQUFDLElBQUksUUFBUSxFQUFFO1lBQzlDLE1BQU0sRUFBQyxRQUFRLEVBQUUsT0FBTyxFQUFDLEdBQW1CLE1BQU0sSUFBSSxDQUFDLElBQUksQ0FBQztnQkFDeEQsTUFBTSxFQUFFLGVBQWU7Z0JBQ3ZCLEtBQUssRUFBRSxJQUFJO2dCQUNYLE1BQU0sRUFBRSxNQUFNLENBQUMsS0FBSyxDQUFDLENBQUMsRUFBRSxDQUFDLEdBQUcsUUFBUSxDQUFDLENBQUMsSUFBSSxDQUFDLEdBQUcsQ0FBQztnQkFDL0MsS0FBSyxFQUFFLEtBQUssQ0FBQyxJQUFJLENBQUMsR0FBRyxDQUFDO2dCQUN0QixHQUFHLEVBQUUsSUFBSTthQUNaLEVBQUUsS0FBSyxDQUFDLENBQUM7WUFDVixJQUFJLENBQUMsT0FBTyxFQUFFO2dCQUNWLE1BQU0sSUFBSSxLQUFLLENBQUMsd0JBQXdCLENBQUMsQ0FBQzthQUM3QztZQUNELE1BQU0sQ0FBQyxNQUFNLENBQUMsV0FBVyxFQUFFLFFBQVEsQ0FBQyxDQUFDO1NBQ3hDO1FBQ0QsT0FBTyxNQUFNLENBQUMsTUFBTSxDQUFDLFdBQVcsQ0FBQyxDQUFDLE1BQU0sQ0FBQyxNQUFNLENBQUMsRUFBRSxDQUFDLE1BQU0sQ0FBQyxPQUFPLEtBQUssU0FBUyxDQUFDLENBQUM7SUFDckYsQ0FBQztDQUNKO0FBNUNHO0lBREMsdUJBQVUsRUFBRTs4Q0FTWjtBQUdEO0lBREMsdUJBQVUsRUFBRTsrQ0FZWjtBQUlEO0lBRkMsbUJBQVEsRUFBRTtJQUNWLHVCQUFVLEVBQUU7K0NBa0JaO0FBbERMLG9DQW1EQyJ9