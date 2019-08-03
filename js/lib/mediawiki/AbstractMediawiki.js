"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const Mediawiki_1 = require("./Mediawiki");
const ThreadPool_1 = require("../ThreadPool");
const cachable_1 = require("../decorators/cachable");
const Logger_1 = __importDefault(require("../Logger"));
const promiseUtils_1 = require("../promiseUtils");
const io_1 = __importDefault(require("../io"));
const node_fetch_1 = __importDefault(require("node-fetch"));
const fetch = require('fetch-cookie/node-fetch')(node_fetch_1.default);
class AbstractMediawiki {
    constructor({ username = Mediawiki_1.MediawikiUsername.OGREBOT_2, api = Mediawiki_1.MediawikiApi.COMMONS, threadPoolSize = 20, throttle = 0 } = {}) {
        this.api = api;
        this.threadPool = new ThreadPool_1.ThreadPoolImpl(threadPoolSize);
        this.throttle = throttle;
        this._ready = (async () => {
            const password = io_1.default.getProperty("secrets", `password_${username}`);
            const logintoken = await this.fetchToken("login");
            const { clientlogin: { status } } = await this.post({
                action: "clientlogin",
                username,
                password,
                loginreturnurl: "http://localhost",
                logintoken
            }, false);
            if (status !== "PASS") {
                throw new Error(`Response not as expected: ${username}, ${api}`);
            }
        })();
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
    fetchToken(type) {
        return this.query({
            meta: "tokens",
            type: type
        }, ["query", "tokens", `${type}token`]);
    }
    async query(parameters, subIndices = []) {
        var result = await this.post({
            ...parameters,
            action: "query"
        }, false);
        for (const index of subIndices) {
            result = result[index];
        }
        return result;
    }
    post(parameters, update) {
        const body = "format=json" + this.encodeFromEntries(Object.entries(parameters), "&", "&");
        return this.fetchAndRead({
            method: 'POST',
            body: body
        }, update);
    }
    fetchAndRead(options = {}, throttled, retries = 3) {
        return new Promise((resolve, reject) => {
            const self = this;
            this.threadPool.enqueue(async function doQuery() {
                Logger_1.default.debug(`query: ${JSON.stringify(options)}`);
                await (throttled && promiseUtils_1.sleep(self.throttle));
                const response = await fetch(self.api, options);
                const responseText = await response.text();
                Logger_1.default.debug(`response: ${responseText.substring(0, 300)}`);
                try {
                    const responseTextJson = JSON.parse(responseText);
                    const { warnings, error } = responseTextJson;
                    if (warnings) {
                        Logger_1.default.warn("Response had warnings. ", warnings);
                    }
                    if (error) {
                        Logger_1.default.warn("Response had errors. ", error);
                    }
                    resolve(responseTextJson);
                }
                catch (e) {
                    if (--retries < 1) {
                        Logger_1.default.error("Error, can't parse response text.", responseText, e);
                        reject(e);
                    }
                    else {
                        await doQuery();
                    }
                }
            });
        });
    }
    encodeFromEntries(entries, sep, prefix) {
        return (entries.length ? prefix : "") + entries.map(([key, val]) => `${encodeURIComponent(key)}=${encodeURIComponent(val)}`).join("&");
    }
}
__decorate([
    cachable_1.cachable()
], AbstractMediawiki.prototype, "fetchToken", null);
exports.AbstractMediawiki = AbstractMediawiki;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiQWJzdHJhY3RNZWRpYXdpa2kuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJBYnN0cmFjdE1lZGlhd2lraS50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7OztBQUFBLDJDQUE4RDtBQUM5RCw4Q0FBMkQ7QUFDM0QscURBQWtEO0FBQ2xELHVEQUErQjtBQUMvQixrREFBd0M7QUFDeEMsK0NBQXVCO0FBQ3ZCLDREQUFtQztBQUNuQyxNQUFNLEtBQUssR0FBRyxPQUFPLENBQUMseUJBQXlCLENBQUMsQ0FBQyxvQkFBUyxDQUFDLENBQUE7QUFFM0QsTUFBc0IsaUJBQWlCO0lBT25DLFlBQVksRUFBRSxRQUFRLEdBQUcsNkJBQWlCLENBQUMsU0FBUyxFQUFFLEdBQUcsR0FBRyx3QkFBWSxDQUFDLE9BQU8sRUFBRSxjQUFjLEdBQUcsRUFBRSxFQUFFLFFBQVEsR0FBRyxDQUFDLEVBQUMsR0FBRyxFQUFFO1FBQ3JILElBQUksQ0FBQyxHQUFHLEdBQUcsR0FBRyxDQUFDO1FBQ2YsSUFBSSxDQUFDLFVBQVUsR0FBRyxJQUFJLDJCQUFjLENBQUMsY0FBYyxDQUFDLENBQUM7UUFDckQsSUFBSSxDQUFDLFFBQVEsR0FBRyxRQUFRLENBQUM7UUFFekIsSUFBSSxDQUFDLE1BQU0sR0FBRyxDQUFDLEtBQUssSUFBSSxFQUFFO1lBQ3RCLE1BQU0sUUFBUSxHQUFHLFlBQUUsQ0FBQyxXQUFXLENBQUMsU0FBUyxFQUFFLFlBQVksUUFBUSxFQUFFLENBQUMsQ0FBQztZQUNuRSxNQUFNLFVBQVUsR0FBRyxNQUFNLElBQUksQ0FBQyxVQUFVLENBQUMsT0FBTyxDQUFDLENBQUM7WUFDbEQsTUFBTSxFQUFDLFdBQVcsRUFBRSxFQUFDLE1BQU0sRUFBQyxFQUFDLEdBQUcsTUFBTSxJQUFJLENBQUMsSUFBSSxDQUFDO2dCQUM1QyxNQUFNLEVBQUUsYUFBYTtnQkFDckIsUUFBUTtnQkFDUixRQUFRO2dCQUNSLGNBQWMsRUFBRSxrQkFBa0I7Z0JBQ2xDLFVBQVU7YUFD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