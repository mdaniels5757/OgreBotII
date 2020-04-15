export enum MediawikiApi {
    COMMONS = "https://commons.wikimedia.org/w/api.php",
    EN_WIKI = "https://en.wikipedia.org/w/api.php",
    WIKIDATA = "https://www.wikidata.org/w/api.php"
}

export enum MediawikiUsername {
    OGREBOT = "MDanielsBot",
    OGREBOT_2 = "MDanielsBot"
}

export interface Mediawiki {
    categoryMembers(category: string): Promise<string[]>;
    categoryMembersRecurse(category: string, depth: number): Promise<string[][]>;
    editAppend(title: string, summary: string, text: string): Promise<void>
}

export const CATEGORY_PREFIX = "Category:";
