import { awaitReady } from "../decorators/awaitReady";
import { AbstractMediawiki } from "./AbstractMediawiki";
import { cachable } from "../decorators/cachable";
import { CATEGORY_PREFIX, Mediawiki } from "./Mediawiki";

export class MediawikiImpl extends AbstractMediawiki implements Mediawiki {
    
    @awaitReady()
    async editAppend(title: string, summary: string, text: string): Promise<void> {
        await this.post({
            action: "edit",
            appendtext: text,
            title: title,
            summary: summary,
            starttimestamp: this.getNowString(),
            //watchlist: "nochange",
            token: await this.fetchToken("csrf") 
        }, true);
    }

    @cachable()
    @awaitReady()
    async categoryMembersRecurse(category: string, depth: number): Promise<string[][]> {
        if (depth > 0) {
            var members: string[][] = [];
            const currentMembers = await this.categoryMembers(category);
            await Promise.all(currentMembers.map(async prefixedMember => {
                if (prefixedMember.startsWith(CATEGORY_PREFIX)) {
                    const member = prefixedMember.substring(CATEGORY_PREFIX.length);
                    const newMembers = await this.categoryMembersRecurse(member, depth - 1);
                    newMembers.forEach(newMember => newMember.unshift(prefixedMember));
                    members.push(...newMembers);
                } else {
                    members.push([prefixedMember]);
                }
            }));
            return members.sort(([a], [b]) => a.localeCompare(b));
        } else {
           return [[]];
        }
    }

    @cachable()
    @awaitReady()
    async categoryMembers(category: string): Promise<string[]> {
        const values = await this.query({
            generator: "categorymembers",
            gcmtitle: `${CATEGORY_PREFIX}${category}`,
            prop: "info",
            gcmlimit: "max"
        }, ["query", "pages"]);

        return values ? Object.values(values).map(o => (<any>o).title) : [];
    }
}