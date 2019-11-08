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
            const [, countyName] = id && style && id.match(/^[A-Z]{2}_([A-Za-z]+)$/) || [];
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
                            const stateCounty = stateCounties[county.toLowerCase()];
                            if (!stateCounty) {
                                console.error(`County ${county}, ${state.name} not found in SVG`);
                                continue;
                            }
                            delete stateCounties[county.toLowerCase()];
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
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiTWFwcy5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbIk1hcHMudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7Ozs7QUFDQSxvREFBMkI7QUFDM0IsK0NBQXVCO0FBQ3ZCLDRDQUFvQjtBQUlwQixNQUFNLE9BQU8sR0FBRyxJQUFJLGdCQUFLLENBQUMsT0FBTyxFQUFFLENBQUM7QUFDcEMsTUFBTSxPQUFPLEdBQUcsR0FBRyxZQUFFLENBQUMsV0FBVyxjQUFjLENBQUM7QUFHaEQscUJBQXFCO0FBQ3JCLG1CQUFtQjtBQUNuQixJQUFJO0FBQ0osTUFBTSxNQUFNO0lBQ1IsWUFBNEIsSUFBWSxFQUFVLElBQVU7UUFBaEMsU0FBSSxHQUFKLElBQUksQ0FBUTtRQUFVLFNBQUksR0FBSixJQUFJLENBQU07SUFDNUQsQ0FBQztJQUVELElBQVcsSUFBSSxDQUFDLEtBQWE7UUFDekIsSUFBSSxDQUFDLElBQUksQ0FBQyxLQUFLLEdBQUcsSUFBSSxDQUFDLElBQUksQ0FBQyxLQUFLLENBQUMsT0FBTyxDQUFDLGFBQWEsRUFBRSxTQUFTLEtBQUssR0FBRyxDQUFDLENBQUM7SUFDaEYsQ0FBQztDQUNKO0FBR0QsTUFBTSxLQUFLO0lBSVAsWUFBNEIsSUFBWSxFQUFVLEdBQVE7UUFBOUIsU0FBSSxHQUFKLElBQUksQ0FBUTtRQUFVLFFBQUcsR0FBSCxHQUFHLENBQUs7UUFDdEQsTUFBTSxRQUFRLEdBQWEsRUFBRSxDQUFDO1FBQzlCLEtBQUssTUFBTSxFQUFDLENBQUMsRUFBQyxJQUFJLEdBQUcsQ0FBQyxHQUFHLENBQUMsQ0FBQyxDQUFDLENBQUMsQ0FBQyxDQUFDLElBQUksRUFBRTtZQUNqQyxNQUFNLEVBQUMsRUFBRSxFQUFFLEtBQUssRUFBQyxHQUEyQixDQUFDLENBQUM7WUFDOUMsTUFBTSxDQUFDLEVBQUUsVUFBVSxDQUFDLEdBQUcsRUFBRSxJQUFJLEtBQUssSUFBSSxFQUFFLENBQUMsS0FBSyxDQUFDLHdCQUF3QixDQUFDLElBQUksRUFBRSxDQUFDO1lB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