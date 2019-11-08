import fs from "fs";
import fetch from "node-fetch";
import Io from "./io";
import { stringHash } from "./stringUtils";

interface CacheEntry {
    timestamp: number;
    url: string;
    contents: string;
}

const MILLIS_PER_SECOND = 1000;
const SECONDS_PER_MINUTE = 60;
const MILLIS_PER_MINUTE = MILLIS_PER_SECOND * SECONDS_PER_MINUTE;
const MINUTES_PER_HOUR = 60;
const HOURS_PER_DAY = 24;
const TEMP_DIR = `${Io.PROJECT_DIR}/tmp/cache/`;
export class HttpFetch {

    constructor(private _timeout: number = MINUTES_PER_HOUR * HOURS_PER_DAY * 30) {

    }
    public get timeout() {
        return this._timeout;
    }

    public set timeout(timeout: number) {
        this._timeout = timeout;
    }

    public async fetch(myUrl: string): Promise<string> {
        const fileName = `${TEMP_DIR}${stringHash(myUrl)}.json`;
        const now = new Date()[Symbol.toPrimitive]("number");
        var obj: CacheEntry[] = [];
        try {
            obj = JSON.parse(fs.readFileSync(fileName).toString());
            for (const [index, {timestamp, url, contents}] of Object.entries(obj)) {
                if (myUrl === url) {
                    if (now - timestamp < this.timeout * MILLIS_PER_MINUTE) {
                        return contents;
                    } else {
                        obj = obj.splice(+index, 1);
                        break;
                    }
                }
            }
        } catch {
            obj = [];
        }

        
        const contents = await (await fetch(myUrl)).text();
        obj.push({
            contents,
            url: myUrl,
            timestamp: now
        });
        fs.writeFileSync(fileName, JSON.stringify(obj));

        return contents;
    }
}