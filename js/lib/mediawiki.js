"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const nodeFetch = require('node-fetch');
const fetch = require('fetch-cookie/node-fetch')(nodeFetch);
const io_1 = __importDefault(require("./io"));
var MediawikiApi;
(function (MediawikiApi) {
    MediawikiApi["COMMONS"] = "https://commons.wikimedia.org/w/api.php";
})(MediawikiApi || (MediawikiApi = {}));
var MediawikiUsername;
(function (MediawikiUsername) {
    MediawikiUsername["OGREBOT_2"] = "OgreBot_2";
})(MediawikiUsername || (MediawikiUsername = {}));
class MediawikiImpl {
    constructor(options) {
        this.tokens = new Map();
        this.username = options.username;
        this.api = options.api;
        this._ready = new Promise(async (resolve, reject) => {
            try {
                const password = io_1.default.getProperty("secrets", `password_${this.username}`);
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
                }
                else {
                    reject("Response not as expected");
                }
            }
            catch (e) {
                console.error(e);
                reject(e);
            }
        });
    }
    get ready() {
        return this._ready;
    }
    getNowString() {
        function zeroPad(value) {
            return String(value).substr(-2);
        }
        var date = new Date();
        return `${date.getFullYear()}${zeroPad(date.getMonth() - 1)}${zeroPad(date.getDate())}${zeroPad(date.getHours())}${zeroPad(date.getMinutes())}${zeroPad(date.getSeconds())}`;
    }
    async editAppend(title, summary, text) {
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
    async categoryMembers(category) {
        return Object.values(await this.query({
            generator: "categorymembers",
            gcmtitle: `Category:${category}`,
            prop: "info",
            gcmlimit: "max"
        }, ["query", "pages"])).map(o => o.title);
    }
    fetchToken(type) {
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
    async query(parameters, subIndices) {
        var result = await this.post({
            ...parameters,
            action: "query"
        });
        for (const index of subIndices) {
            result = result[index];
        }
        return result;
    }
    post(parameters) {
        const body = "format=json" + this.encodeFromEntries(Object.entries(parameters), "&", "&");
        return this.fetchAndRead({
            method: 'POST',
            body: body
        });
    }
    async fetchAndRead(options = {}) {
        const response = await fetch(this.api, options);
        const responseText = JSON.parse(await response.text());
        const { warnings, error } = responseText;
        if (warnings) {
            console.warn("Response had warnings. ", warnings);
        }
        if (error) {
            console.warn("Response had errors. ", error);
        }
        return responseText;
    }
    encodeFromEntries(entries, sep, prefix) {
        return (entries.length ? prefix : "") + entries.map(([key, val]) => `${encodeURIComponent(key)}=${encodeURIComponent(val)}`).join("&");
    }
}
class MediawikiWrapper {
    constructor(options = {
        username: MediawikiUsername.OGREBOT_2,
        api: MediawikiApi.COMMONS
    }) {
        var impl = new MediawikiImpl(options);
        function promisify(fn) {
            return async (...args) => {
                await impl.ready;
                return fn.apply(impl, args);
            };
        }
        ;
        this.categoryMembers = promisify(impl.categoryMembers);
        this.editAppend = promisify(impl.editAppend);
    }
}
;
function getMediawiki(options = {
    username: MediawikiUsername.OGREBOT_2,
    api: MediawikiApi.COMMONS
}) {
    return new MediawikiWrapper(options);
}
exports.default = getMediawiki;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoibWVkaWF3aWtpLmpzIiwic291cmNlUm9vdCI6IiIsInNvdXJjZXMiOlsibWVkaWF3aWtpLnRzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiI7Ozs7O0FBQUEsTUFBTSxTQUFTLEdBQUcsT0FBTyxDQUFDLFlBQVksQ0FBQyxDQUFBO0FBQ3ZDLE1BQU0sS0FBSyxHQUFHLE9BQU8sQ0FBQyx5QkFBeUIsQ0FBQyxDQUFDLFNBQVMsQ0FBQyxDQUFBO0FBRzNELDhDQUFzQjtBQUV0QixJQUFLLFlBRUo7QUFGRCxXQUFLLFlBQVk7SUFDYixtRUFBbUQsQ0FBQTtBQUN2RCxDQUFDLEVBRkksWUFBWSxLQUFaLFlBQVksUUFFaEI7QUFFRCxJQUFLLGlCQUVKO0FBRkQsV0FBSyxpQkFBaUI7SUFDbEIsNENBQXVCLENBQUE7QUFDM0IsQ0FBQyxFQUZJLGlCQUFpQixLQUFqQixpQkFBaUIsUUFFckI7QUFnQkQsTUFBTSxhQUFhO0lBT2YsWUFBWSxPQUF5QjtRQUY3QixXQUFNLEdBQUcsSUFBSSxHQUFHLEVBQTJCLENBQUM7UUFHaEQsSUFBSSxDQUFDLFFBQVEsR0FBRyxPQUFPLENBQUMsUUFBUSxDQUFDO1FBQ2pDLElBQUksQ0FBQyxHQUFHLEdBQUcsT0FBTyxDQUFDLEdBQUcsQ0FBQztRQUV2QixJQUFJLENBQUMsTUFBTSxHQUFHLElBQUksT0FBTyxDQUFDLEtBQUssRUFBRSxPQUFPLEVBQUUsTUFBTSxFQUFFLEVBQUU7WUFDaEQsSUFBSTtnQkFDQSxNQUFNLFFBQVEsR0FBRyxZQUFFLENBQUMsV0FBVyxDQUFDLFNBQVMsRUFBRSxZQUFZLElBQUksQ0FBQyxRQUFRLEVBQUUsQ0FBQyxDQUFDO2dCQUN4RSxNQUFNLFVBQVUsR0FBRyxNQUFNLElBQUksQ0FBQyxVQUFVLENBQUMsT0FBTyxDQUFDLENBQUM7Z0JBQ2xELE1BQU0sUUFBUSxHQUFHLE1BQU0sSUFBSSxDQUFDLElBQUksQ0FBQztvQkFDN0IsTUFBTSxFQUFFLE9BQU87b0JBQ2YsTUFBTSxFQUFFLElBQUksQ0FBQyxRQUFR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