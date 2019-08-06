import { AbstractMediawiki } from "./AbstractMediawiki";
import { MediawikiUsername, MediawikiApi } from "./Mediawiki";
import { awaitReady } from "../decorators/awaitReady";
import { cachable } from "../decorators/cachable";


export type SNAKTYPE = "value" | "novalue" | "somevalue";
export interface Snak {
    snaktype: SNAKTYPE
    property: string
    hash: string
    datavalue: any
    datatype: string
}

interface Claim {
    mainsnak: Snak
    type: string
    id: string
    rank: string
}

interface SiteLink {
    site: string,
    title: string
    badges: string[]
}

interface Entity {
    missing?: ""
    type: string,
    id: string
    sitelinks?: {[s: string]: SiteLink}
    claims?: {[s: string]: Claim}
}

interface EntityResponse {
    entities: {[s: string]: Entity},
    success: 1|0
}


export interface Wikidata {
    linktitles(tosite: string, totitle: string, fromsite: string, fromtitle: string): Promise<boolean>;
    createclaim(entity: string, property: string, value: any, snaktype?: SNAKTYPE): Promise<boolean>; 
    getentities(site: string, titles: string[], props: string[]): Promise<Entity[]>;
}


export class WikidataImpl extends AbstractMediawiki implements Wikidata {

    constructor() {
        super({username: MediawikiUsername.OGREBOT, api: MediawikiApi.WIKIDATA, threadPoolSize: 2, throttle: 0})
    }

    @awaitReady()
    async linktitles(tosite: string, totitle: string, fromsite: string, fromtitle: string) {
        const {success} = await this.post({
            action: "wblinktitles",
            bot: true,
            token: await this.fetchToken("csrf"),
            tosite, totitle, fromtitle, fromsite
        }, true);
        return !!success;
    }

    @awaitReady()
    async createclaim(entity: string, property: string, value: any, snaktype: SNAKTYPE= "value") {
        const {success} = await this.post({
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

    @cachable()
    @awaitReady() 
    async getentities(site: string, titles: string[], props: string[]) {
        const MAX_SIZE = 500;
        const allEntities : {[s: string]: Entity} = {};
        for (var i = 0; i < titles.length; i += MAX_SIZE) {
            const {entities, success} = <EntityResponse>await this.post({
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