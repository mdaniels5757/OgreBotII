import { MediawikiApi, MediawikiUsername } from "./Mediawiki";
import { ThreadPool, ThreadPoolImpl } from "../ThreadPool";
import { cachable } from "../decorators/cachable";
import logger from "../Logger";
import { sleep } from "../promiseUtils";
import Io from "../io";
import nodeFetch from "node-fetch";
const fetch = require('fetch-cookie/node-fetch')(nodeFetch)

export abstract class AbstractMediawiki {

    private api: MediawikiApi;
    private _ready: Promise<void>;
    private threadPool: ThreadPool;
    private throttle: number;

    constructor({ username = MediawikiUsername.OGREBOT_2, api = MediawikiApi.COMMONS, threadPoolSize = 20, throttle = 0} = {}) {
        this.api = api;        
        this.threadPool = new ThreadPoolImpl(threadPoolSize);
        this.throttle = throttle;

        this._ready = (async () => {
            const password = Io.getProperty("secrets", `password_${username}`);
            const logintoken = await this.fetchToken("login");
            const {clientlogin: {status}} = await this.post({
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


    protected getNowString() {
        function zeroPad(value: number) {
            return String(value).substr(-2);
        }
        var date = new Date();
        
        return `${date.getFullYear()}${zeroPad(date.getMonth() - 1)}${zeroPad(date.getDate())}${zeroPad(date.getHours())}${zeroPad(date.getMinutes())}${zeroPad(date.getSeconds())}`
    }
    
    @cachable()
    protected fetchToken(type: string): Promise<string> {
        return this.query({
            meta: "tokens",
            type: type
        }, ["query", "tokens", `${type}token`]);
    }

    protected async query(parameters: object, subIndices: string[] = []) {
        
        var result = await this.post({
            ... parameters,
            action: "query"
        }, false);

        for (const index of subIndices) {
            result = result[index];
        }

        return result;
    }

    protected post(parameters: object, update: boolean) {
        const body = "format=json" + this.encodeFromEntries(Object.entries(parameters), "&", "&");

        return this.fetchAndRead({
            method: 'POST',
            body: body
        }, update);
    }

    protected fetchAndRead(options: object = {}, throttled: boolean, retries = 3): Promise<any> {
        return new Promise((resolve, reject) => {

            const self = this;
            this.threadPool.enqueue(async function doQuery() {
                logger.debug(`query: ${JSON.stringify(options)}`);
                await (throttled && sleep(self.throttle));
                const response = await fetch(self.api, options);
                const responseText = await response.text();
                logger.debug(`response: ${responseText.substring(0, 300)}`);
                try {
                    const responseTextJson = JSON.parse(responseText);
        
                    const {warnings, error} = responseTextJson;
                    if (warnings) {
                        logger.warn("Response had warnings. ", warnings);
                    }
                    if (error) {
                        logger.warn("Response had errors. ", error);
                    }
            
                    resolve(responseTextJson);
                } catch (e) {
                    if (--retries < 1) {
                        logger.error("Error, can't parse response text.", responseText, e);
                        reject(e);
                    } else {
                        await doQuery();
                    }
                }
            });
        });

    }

    protected encodeFromEntries(entries: any[], sep: string, prefix: string)  {
        return (entries.length ? prefix : "") + entries.map(([key, val]) => `${encodeURIComponent(key)}=${encodeURIComponent(val)}`).join("&");
    }
}