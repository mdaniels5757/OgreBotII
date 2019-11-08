"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const fs_1 = __importDefault(require("fs"));
const node_fetch_1 = __importDefault(require("node-fetch"));
const io_1 = __importDefault(require("./io"));
const stringUtils_1 = require("./stringUtils");
const MILLIS_PER_SECOND = 1000;
const SECONDS_PER_MINUTE = 60;
const MILLIS_PER_MINUTE = MILLIS_PER_SECOND * SECONDS_PER_MINUTE;
const MINUTES_PER_HOUR = 60;
const HOURS_PER_DAY = 24;
const TEMP_DIR = `${io_1.default.PROJECT_DIR}/tmp/cache/`;
class HttpFetch {
    constructor(_timeout = MINUTES_PER_HOUR * HOURS_PER_DAY * 30) {
        this._timeout = _timeout;
    }
    get timeout() {
        return this._timeout;
    }
    set timeout(timeout) {
        this._timeout = timeout;
    }
    async fetch(myUrl) {
        const fileName = `${TEMP_DIR}${stringUtils_1.stringHash(myUrl)}.json`;
        const now = new Date()[Symbol.toPrimitive]("number");
        var obj = [];
        try {
            obj = JSON.parse(fs_1.default.readFileSync(fileName).toString());
            for (const [index, { timestamp, url, contents }] of Object.entries(obj)) {
                if (myUrl === url) {
                    if (now - timestamp < this.timeout * MILLIS_PER_MINUTE) {
                        return contents;
                    }
                    else {
                        obj = obj.splice(+index, 1);
                        break;
                    }
                }
            }
        }
        catch {
            obj = [];
        }
        const contents = await (await node_fetch_1.default(myUrl)).text();
        obj.push({
            contents,
            url: myUrl,
            timestamp: now
        });
        fs_1.default.writeFileSync(fileName, JSON.stringify(obj));
        return contents;
    }
}
exports.HttpFetch = HttpFetch;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiaHR0cC5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbImh0dHAudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7Ozs7QUFBQSw0Q0FBb0I7QUFDcEIsNERBQStCO0FBQy9CLDhDQUFzQjtBQUN0QiwrQ0FBMkM7QUFRM0MsTUFBTSxpQkFBaUIsR0FBRyxJQUFJLENBQUM7QUFDL0IsTUFBTSxrQkFBa0IsR0FBRyxFQUFFLENBQUM7QUFDOUIsTUFBTSxpQkFBaUIsR0FBRyxpQkFBaUIsR0FBRyxrQkFBa0IsQ0FBQztBQUNqRSxNQUFNLGdCQUFnQixHQUFHLEVBQUUsQ0FBQztBQUM1QixNQUFNLGFBQWEsR0FBRyxFQUFFLENBQUM7QUFDekIsTUFBTSxRQUFRLEdBQUcsR0FBRyxZQUFFLENBQUMsV0FBVyxhQUFhLENBQUM7QUFDaEQsTUFBYSxTQUFTO0lBRWxCLFlBQW9CLFdBQW1CLGdCQUFnQixHQUFHLGFBQWEsR0FBRyxFQUFFO1FBQXhELGFBQVEsR0FBUixRQUFRLENBQWdEO0lBRTVFLENBQUM7SUFDRCxJQUFXLE9BQU87UUFDZCxPQUFPLElBQUksQ0FBQyxRQUFRLENBQUM7SUFDekIsQ0FBQztJQUVELElBQVcsT0FBTyxDQUFDLE9BQWU7UUFDOUIsSUFBSSxDQUFDLFFBQVEsR0FBRyxPQUFPLENBQUM7SUFDNUIsQ0FBQztJQUVNLEtBQUssQ0FBQyxLQUFLLENBQUMsS0FBYTtRQUM1QixNQUFNLFFBQVEsR0FBRyxHQUFHLFFBQVEsR0FBRyx3QkFBVSxDQUFDLEtBQUssQ0FBQyxPQUFPLENBQUM7UUFDeEQsTUFBTSxHQUFHLEdBQUcsSUFBSSxJQUFJLEVBQUUsQ0FBQyxNQUFNLENBQUMsV0FBVyxDQUFDLENBQUMsUUFBUSxDQUFDLENBQUM7UUFDckQsSUFBSSxHQUFHLEdBQWlCLEVBQUUsQ0FBQztRQUMzQixJQUFJO1lBQ0EsR0FBRyxHQUFHLElBQUksQ0FBQyxLQUFLLENBQUMsWUFBRSxDQUFDLFlBQVksQ0FBQyxRQUFRLENBQUMsQ0FBQyxRQUFRLEVBQUUsQ0FBQyxDQUFDO1lBQ3ZELEtBQUssTUFBTSxDQUFDLEtBQUssRUFBRSxFQUFDLFNBQVMsRUFBRSxHQUFHLEVBQUUsUUFBUSxFQUFDLENBQUMsSUFBSSxNQUFNLENBQUMsT0FBTyxDQUFDLEdBQUcsQ0FBQyxFQUFFO2dCQUNuRSxJQUFJLEtBQUssS0FBSyxHQUFHLEVBQUU7b0JBQ2YsSUFBSSxHQUFHLEdBQUcsU0FBUyxHQUFHLElBQUksQ0FBQyxPQUFPLEdBQUcsaUJBQWlCLEVBQUU7d0JBQ3BELE9BQU8sUUFBUSxDQUFDO3FCQUNuQjt5QkFBTTt3QkFDSCxHQUFHLEdBQUcsR0FBRyxDQUFDLE1BQU0sQ0FBQyxDQUFDLEtBQUssRUFBRSxDQUFDLENBQUMsQ0FBQzt3QkFDNUIsTUFBTTtxQkFDVDtpQkFDSjthQUNKO1NBQ0o7UUFBQyxNQUFNO1lBQ0osR0FBRyxHQUFHLEVBQUUsQ0FBQztTQUNaO1FBR0QsTUFBTSxRQUFRLEdBQUcsTUFBTSxDQUFDLE1BQU0sb0JBQUssQ0FBQyxLQUFLLENBQUMsQ0FBQyxDQUFDLElBQUksRUFBRSxDQUFDO1FBQ25ELEdBQUcsQ0FBQyxJQUFJLENBQUM7WUFDTCxRQUFRO1lBQ1IsR0FBRyxFQUFFLEtBQUs7WUFDVixTQUFTLEVBQUUsR0FBRztTQUNqQixDQUFDLENBQUM7UUFDSCxZQUFFLENBQUMsYUFBYSxDQUFDLFFBQVEsRUFBRSxJQUFJLENBQUMsU0FBUyxDQUFDLEdBQUcsQ0FBQyxDQUFDLENBQUM7UUFFaEQsT0FBTyxRQUFRLENBQUM7SUFDcEIsQ0FBQztDQUNKO0FBNUNELDhCQTRDQyJ9