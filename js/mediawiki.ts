const nodeFetch = require('node-fetch')
const fetch = require('fetch-cookie/node-fetch')(nodeFetch)

import {Response} from "node-fetch";
import io from "./io";

enum MediawikiApi {
    COMMONS = "https://commons.wikimedia.org/w/api.php"
}

enum MediawikiUsername {
    OGREBOT_2 = "OgreBot_2"
}

interface MediawikiOptions {
    username: MediawikiUsername;
    api: MediawikiApi
}

interface Mediawiki {
    categoryMembers(category: string): Promise<string[]>;
    editAppend(title: string, summary: string, text: string): Promise<void>
}

interface MediawikiDateField {
    name: string
}

class MediawikiImpl implements Mediawiki {

    private username: MediawikiUsername;
    private api: MediawikiApi;
    private _ready: Promise<Mediawiki>;
    private tokens = new Map<string, Promise<string>>();
    
    constructor(options: MediawikiOptions) {
        this.username = options.username;
        this.api = options.api;        
        
        this._ready = new Promise(async (resolve, reject) => {
            try {
                const password = io.getProperty("secrets", `password_${this.username}`);
                const loginToken = await this.fetchToken("login");
                const response = await this.post({
                    action: "login",
                    lgname: this.username,
                    lgpassword: password,
                    lgtoken: loginToken
                });
                this.tokens.clear();
                if (response.login.result === "Success") {
                    resolve(this);
                } else {
                    reject("Response not as expected");
                }
            } catch(e) {
                console.error(e);
                reject(e);
            }
        });
    }

    get ready() {
        return this._ready;
    }

    private getNowString() {
        function zeroPad(value: number) {
            return String(value).substr(-2);
        }
        var date = new Date();

        return `${date.getFullYear()}${zeroPad(date.getMonth() - 1)}${zeroPad(date.getDate())}${zeroPad(date.getHours())}${zeroPad(date.getMinutes())}${zeroPad(date.getSeconds())}`
    }

    async editAppend(title: string, summary: string, text: string): Promise<void> {
        await this.post({
            action: "edit",
            appendtext: text,
            title: title,
            summary: summary,
            starttimestamp: this.getNowString(),
            //watchlist: "nochange",
            token: await this.fetchToken("csrf") 
        });
    }

    async categoryMembers(category: string): Promise<string[]> {
        return Object.values(await this.query({
            generator: "categorymembers",
            gcmtitle: `Category:${category}`,
            prop: "info",
            gcmlimit: "max"
        }, ["query", "pages"])).map(o => (<any>o).title);
    }

    private fetchToken(type: string): Promise<string> {
        var tokenPromise = this.tokens.get(type);
        if (!tokenPromise) {
            tokenPromise = this.query({
                meta: "tokens",
                type: type
            }, ["query", "tokens", `${type}token`]);
            this.tokens.set(type, tokenPromise);
        }
        return tokenPromise;
    }

    private async query(parameters: object, subIndices: string[]) {
        var result = await this.post({
            ... parameters,
            action: "query"
        });

        for (const index of subIndices) {
            result = result[index];
        }

        return result;
    }

    private post(parameters: object) {
        const body = "format=json" + this.encodeFromEntries(Object.entries(parameters), "&", "&");

        return this.fetchAndRead({
            method: 'POST',
            body: body
        });
    }

    private async fetchAndRead(options: object = {}) {
        const response = await fetch(this.api, options);
        const responseText = JSON.parse(await response.text());

        const {warnings, error} = responseText;
        if (warnings) {
            console.warn("Response had warnings. ", warnings);
        }
        if (error) {
            console.warn("Response had errors. ", error);
        }

        return responseText;
    }

    private encodeFromEntries(entries: any[], sep: string, prefix: string)  {
        return (entries.length ? prefix : "") + entries.map(([key, val]) => `${encodeURIComponent(key)}=${encodeURIComponent(val)}`).join("&");
    }
}

class MediawikiWrapper implements Mediawiki {

    categoryMembers: (category: string) => Promise<string[]>;
    editAppend: (title: string, summary: string, text: string) => Promise<void>;

    public constructor(options: MediawikiOptions = {
        username: MediawikiUsername.OGREBOT_2,
        api: MediawikiApi.COMMONS
    }) {

        var impl  = new MediawikiImpl(options);

        function promisify <T> (fn: (...args: any[]) => Promise<T>) {
            return async (...args: any[]) => {
                await impl.ready;
                return fn.apply(impl, args);
            };
        };

        this.categoryMembers = promisify(impl.categoryMembers);
        this.editAppend = promisify(impl.editAppend);
    }        
};

export default function getMediawiki(options: MediawikiOptions = {
    username: MediawikiUsername.OGREBOT_2,
    api: MediawikiApi.COMMONS
}): Mediawiki {
    return new MediawikiWrapper(options);
}