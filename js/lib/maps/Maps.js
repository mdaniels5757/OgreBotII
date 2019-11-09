"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const xml2js_1 = __importDefault(require("xml2js"));
const io_1 = __importDefault(require("../io"));
const fs_1 = __importDefault(require("fs"));
const builder = new xml2js_1.default.Builder();
const mapsDir = `${io_1.default.PROJECT_DIR}/county-maps`;
// enum County_Type {
//     COUNTY, CITY
// }
class County {
    constructor(name, path) {
        this.name = name;
        this.path = path;
    }
    set fill(color) {
        this.path.style = this.path.style.replace(";fill:#888;", `;fill:${color};`);
    }
}
class State {
    constructor(name, xml) {
        this.name = name;
        this.xml = xml;
        const counties = [];
        for (const { $ } of xml.svg.g[0].path) {
            const { id, style } = $;
            const [, countyName] = id && style && id.match(/^[A-Z]{2}_([A-Za-z_]+)$/) || [];
            countyName && counties.push(new County(countyName, $));
        }
        this.counties = Object.seal(counties);
    }
    async write(dest) {
        await fs_1.default.writeFileSync(dest, builder.buildObject(this.xml));
    }
}
class StatesBuilder {
    constructor() {
        this.electionTrackers = [];
    }
    addElectionTrackers(...electionTrackers) {
        this.electionTrackers.push(...electionTrackers);
        return this;
    }
    setElectionResultHandler(electionResultColorer) {
        this.electionResultColorer = electionResultColorer;
        return this;
    }
    async build(dest) {
        var { electionResultColorer, electionTrackers } = this;
        if (!electionResultColorer || !electionTrackers[0]) {
            throw new Error("build() called before ready");
        }
        const promises = function* () {
            for (const file of io_1.default.readDir(mapsDir)) {
                const path = `${mapsDir}/${file}`;
                const stateName = file.replace(/\.svg$/, "");
                const tracker = electionTrackers.find(tracker => tracker.isEligible(stateName));
                if (tracker) {
                    yield (async () => {
                        const text = fs_1.default.readFileSync(path).toString();
                        const xml = await xml2js_1.default.parseStringPromise(text);
                        const state = new State(stateName, xml);
                        const stateCounties = Object.fromEntries(state.counties.map(county => [county.name.toLowerCase(), county]));
                        for (const [county, { r, d, i }] of Object.entries(await tracker.getCountyTotals())) {
                            const normalizedCounty = county.toLowerCase().replace(/ /g, "_");
                            const stateCounty = stateCounties[normalizedCounty];
                            if (!stateCounty) {
                                console.error(`County ${county}, ${state.name} not found in SVG`);
                                continue;
                            }
                            delete stateCounties[normalizedCounty];
                            const total = r + d + i;
                            stateCounty.fill = electionResultColorer(...(() => {
                                if (r > d && r > i) {
                                    return ["r", r / total];
                                }
                                else if (d > r && d > i) {
                                    return ["d", d / total];
                                }
                                else if (i > r && i > d) {
                                    return ["i", d / total];
                                }
                                else {
                                    return ["t", Math.max(r, d, i) / total];
                                }
                            })());
                        }
                        for (const county of Object.keys(stateCounties)) {
                            console.error(`County ${county}, ${state.name} not found in election results`);
                        }
                        state.write(`${dest}/${file}`);
                    })();
                }
            }
        }();
        return Promise.all([...promises]);
    }
}
exports.StatesBuilder = StatesBuilder;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiTWFwcy5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbIk1hcHMudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7Ozs7QUFDQSxvREFBMkI7QUFDM0IsK0NBQXVCO0FBQ3ZCLDRDQUFvQjtBQUlwQixNQUFNLE9BQU8sR0FBRyxJQUFJLGdCQUFLLENBQUMsT0FBTyxFQUFFLENBQUM7QUFDcEMsTUFBTSxPQUFPLEdBQUcsR0FBRyxZQUFFLENBQUMsV0FBVyxjQUFjLENBQUM7QUFHaEQscUJBQXFCO0FBQ3JCLG1CQUFtQjtBQUNuQixJQUFJO0FBQ0osTUFBTSxNQUFNO0lBQ1IsWUFBNEIsSUFBWSxFQUFVLElBQVU7UUFBaEMsU0FBSSxHQUFKLElBQUksQ0FBUTtRQUFVLFNBQUksR0FBSixJQUFJLENBQU07SUFDNUQsQ0FBQztJQUVELElBQVcsSUFBSSxDQUFDLEtBQWE7UUFDekIsSUFBSSxDQUFDLElBQUksQ0FBQyxLQUFLLEdBQUcsSUFBSSxDQUFDLElBQUksQ0FBQyxLQUFLLENBQUMsT0FBTyxDQUFDLGFBQWEsRUFBRSxTQUFTLEtBQUssR0FBRyxDQUFDLENBQUM7SUFDaEYsQ0FBQztDQUNKO0FBR0QsTUFBTSxLQUFLO0lBSVAsWUFBNEIsSUFBWSxFQUFVLEdBQVE7UUFBOUIsU0FBSSxHQUFKLElBQUksQ0FBUTtRQUFVLFFBQUcsR0FBSCxHQUFHLENBQUs7UUFDdEQsTUFBTSxRQUFRLEdBQWEsRUFBRSxDQUFDO1FBQzlCLEtBQUssTUFBTSxFQUFDLENBQUMsRUFBQyxJQUFJLEdBQUcsQ0FBQyxHQUFHLENBQUMsQ0FBQyxDQUFDLENBQUMsQ0FBQyxDQUFDLElBQUksRUFBRTtZQUNqQyxNQUFNLEVBQUMsRUFBRSxFQUFFLEtBQUssRUFBQyxHQUEyQixDQUFDLENBQUM7WUFDOUMsTUFBTSxDQUFDLEVBQUUsVUFBVSxDQUFDLEdBQUcsRUFBRSxJQUFJLEtBQUssSUFBSSxFQUFFLENBQUMsS0FBSyxDQUFDLHlCQUF5QixDQUFDLElBQUksRUFBRSxDQUFDO1lBQ2hGLFVBQVUsSUFBSSxRQUFRLENBQUMsSUFBSSxDQUFDLElBQUksTUFBTSxDQUFDLFVBQVUsRUFBRSxDQUFDLENBQUMsQ0FBQyxDQUFDO1NBQzFEO1FBQ0QsSUFBSSxDQUFDLFFBQVEsR0FBRyxNQUFNLENBQUMsSUFBSSxDQUFDLFFBQVEsQ0FBQyxDQUFDO0lBQzFDLENBQUM7SUFFRCxLQUFLLENBQUMsS0FBSyxDQUFDLElBQVk7UUFDcEIsTUFBTSxZQUFFLENBQUMsYUFBYSxDQUFDLElBQUksRUFBRSxPQUFPLENBQUMsV0FBVyxDQUFDLElBQUksQ0FBQyxHQUFHLENBQUMsQ0FBQyxDQUFDO0lBQ2hFLENBQUM7Q0FDSjtBQUVELE1BQWEsYUFBYTtJQUExQjtRQUNZLHFCQUFnQixHQUFzQixFQUFFLENBQUM7SUFrRXJELENBQUM7SUEvREcsbUJBQW1CLENBQUMsR0FBRyxnQkFBbUM7UUFDdEQsSUFBSSxDQUFDLGdCQUFnQixDQUFDLElBQUksQ0FBQyxHQUFHLGdCQUFnQixDQUFDLENBQUM7UUFDaEQsT0FBTyxJQUFJLENBQUM7SUFDaEIsQ0FBQztJQUVELHdCQUF3QixDQUFDLHFCQUE0QztRQUNqRSxJQUFJLENBQUMscUJBQXFCLEdBQUcscUJBQXFCLENBQUM7UUFDbkQsT0FBTyxJQUFJLENBQUM7SUFDaEIsQ0FBQztJQUVELEtBQUssQ0FBQyxLQUFLLENBQUMsSUFBWTtRQUNwQixJQUFJLEVBQUMscUJBQXFCLEVBQUUsZ0JBQWdCLEVBQUMsR0FBRyxJQUFJLENBQUM7UUFDckQsSUFBSSxDQUFDLHFCQUFxQixJQUFJLENBQUMsZ0JBQWdCLENBQUMsQ0FBQyxDQUFDLEVBQUU7WUFDaEQsTUFBTSxJQUFJLEtBQUssQ0FBQyw2QkFBNkIsQ0FBQyxDQUFDO1NBQ2xEO1FBRUQsTUFBTSxRQUFRLEdBQUcsUUFBUSxDQUFDO1lBQ3RCLEtBQUssTUFBTSxJQUFJLElBQUksWUFBRSxDQUFDLE9BQU8sQ0FBQyxPQUFPLENBQUMsRUFBRTtnQkFDcEMsTUFBTSxJQUFJLEdBQUcsR0FBRyxPQUFPLElBQUksSUFBSSxFQUFFLENBQUM7Z0JBQ2xDLE1BQU0sU0FBUyxHQUFHLElBQUksQ0FBQyxPQUFPLENBQUMsUUFBUSxFQUFFLEVBQUUsQ0FBQyxDQUFDO2dCQUM3QyxNQUFNLE9BQU8sR0FBRyxnQkFBZ0IsQ0FBQyxJQUFJLENBQUMsT0FBTyxDQUFDLEVBQUUsQ0FBQyxPQUFPLENBQUMsVUFBVSxDQUFDLFNBQVMsQ0FBQyxDQUFDLENBQUM7Z0JBQ2hGLElBQUksT0FBTyxFQUFFO29CQUNULE1BQU0sQ0FBQyxLQUFLLElBQUcsRUFBRTt3QkFDYixNQUFNLElBQUksR0FBRyxZQUFFLENBQUMsWUFBWSxDQUFDLElBQUksQ0FBQyxDQUFDLFFBQVEsRUFBRSxDQUFDO3dCQUM5QyxNQUFNLEdBQUcsR0FBRyxNQUFNLGdCQUFLLENBQUMsa0JBQWtCLENBQUMsSUFBSSxDQUFDLENBQUM7d0JBQ2pELE1BQU0sS0FBSyxHQUFHLElBQUksS0FBSyxDQUFDLFNBQVMsRUFBRSxHQUFHLENBQUMsQ0FBQzt3QkFFeEMsTUFBTSxhQUFhLEdBQTRCLE1BQU0sQ0FBQyxXQUFXLENBQUMsS0FBSyxDQUFDLFFBQVEsQ0FBQyxHQUFHLENBQUMsTUFBTSxDQUFDLEVBQUUsQ0FBQyxDQUFDLE1BQU0sQ0FBQyxJQUFJLENBQUMsV0FBVyxFQUFFLEVBQUUsTUFBTSxDQUFDLENBQUMsQ0FBQyxDQUFDO3dCQUNySSxLQUFLLE1BQU0sQ0FBQyxNQUFNLEVBQUUsRUFBQyxDQUFDLEVBQUUsQ0FBQyxFQUFFLENBQUMsRUFBQyxDQUFDLElBQUksTUFBTSxDQUFDLE9BQU8sQ0FBQyxNQUFNLE9BQU8sQ0FBQyxlQUFlLEVBQUUsQ0FBQyxFQUFFOzRCQUMvRSxNQUFNLGdCQUFnQixHQUFHLE1BQU0sQ0FBQyxXQUFXLEVBQUUsQ0FBQyxPQUFPLENBQUMsSUFBSSxFQUFFLEdBQUcsQ0FBQyxDQUFDOzRCQUNqRSxNQUFNLFdBQVcsR0FBRyxhQUFhLENBQUMsZ0JBQWdCLENBQUMsQ0FBQzs0QkFDcEQsSUFBSSxDQUFDLFdBQVcsRUFBRTtnQ0FDZCxPQUFPLENBQUMsS0FBSyxDQUFDLFVBQVUsTUFBTSxLQUFLLEtBQUssQ0FBQyxJQUFJLG1CQUFtQixDQUFDLENBQUM7Z0NBQ2xFLFNBQVM7NkJBQ1o7NEJBRUQsT0FBTyxhQUFhLENBQUMsZ0JBQWdCLENBQUMsQ0FBQzs0QkFFdkMsTUFBTSxLQUFLLEdBQUcsQ0FBQyxHQUFHLENBQUMsR0FBRyxDQUFDLENBQUM7NEJBQ3hCLFdBQVcsQ0FBQyxJQUFJLEdBQUcscUJBQXFCLENBQUMsR0FBRyxDQUFDLEdBQXlCLEVBQUU7Z0NBQ3BFLElBQUksQ0FBQyxHQUFHLENBQUMsSUFBSSxDQUFDLEdBQUcsQ0FBQyxFQUFFO29DQUNoQixPQUFPLENBQUMsR0FBRyxFQUFFLENBQUMsR0FBRyxLQUFLLENBQUMsQ0FBQztpQ0FDM0I7cUNBQU0sSUFBSSxDQUFDLEdBQUcsQ0FBQyxJQUFJLENBQUMsR0FBRyxDQUFDLEVBQUU7b0NBQ3ZCLE9BQU8sQ0FBQyxHQUFHLEVBQUUsQ0FBQyxHQUFHLEtBQUssQ0FBQyxDQUFDO2lDQUMzQjtxQ0FBTSxJQUFJLENBQUMsR0FBRyxDQUFDLElBQUksQ0FBQyxHQUFHLENBQUMsRUFBRTtvQ0FDdkIsT0FBTyxDQUFDLEdBQUcsRUFBRSxDQUFDLEdBQUcsS0FBSyxDQUFDLENBQUM7aUNBQzNCO3FDQUFNO29DQUNILE9BQU8sQ0FBQyxHQUFHLEVBQUUsSUFBSSxDQUFDLEdBQUcsQ0FBQyxDQUFDLEVBQUUsQ0FBQyxFQUFFLENBQUMsQ0FBQyxHQUFHLEtBQUssQ0FBQyxDQUFDO2lDQUMzQzs0QkFDTCxDQUFDLENBQUMsRUFBRSxDQUFDLENBQUM7eUJBQ1Q7d0JBRUQsS0FBSyxNQUFNLE1BQU0sSUFBSSxNQUFNLENBQUMsSUFBSSxDQUFDLGFBQWEsQ0FBQyxFQUFFOzRCQUM3QyxPQUFPLENBQUMsS0FBSyxDQUFDLFVBQVUsTUFBTSxLQUFLLEtBQUssQ0FBQyxJQUFJLGdDQUFnQyxDQUFDLENBQUM7eUJBQ2xGO3dCQUNELEtBQUssQ0FBQyxLQUFLLENBQUMsR0FBRyxJQUFJLElBQUksSUFBSSxFQUFFLENBQUMsQ0FBQztvQkFDbkMsQ0FBQyxDQUFDLEVBQUUsQ0FBQztpQkFDUjthQUNKO1FBQ0wsQ0FBQyxFQUFFLENBQUM7UUFFSixPQUFPLE9BQU8sQ0FBQyxHQUFHLENBQUMsQ0FBQyxHQUFHLFFBQVEsQ0FBQyxDQUFDLENBQUM7SUFDdEMsQ0FBQztDQUNKO0FBbkVELHNDQW1FQyJ9