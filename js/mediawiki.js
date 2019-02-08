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
//# sourceMappingURL=mediawiki.js.map