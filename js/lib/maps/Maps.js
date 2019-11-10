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
        this.path.style = this.path.style.split(/\s*;\s*/g).map((style) => style.match(/^\s*fill\s*:\s*\#?\w+\s*$/i) ? `fill:${color}` : style).join(";");
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
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiTWFwcy5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbIk1hcHMudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7Ozs7QUFDQSxvREFBMkI7QUFDM0IsK0NBQXVCO0FBQ3ZCLDRDQUFvQjtBQUlwQixNQUFNLE9BQU8sR0FBRyxJQUFJLGdCQUFLLENBQUMsT0FBTyxFQUFFLENBQUM7QUFDcEMsTUFBTSxPQUFPLEdBQUcsR0FBRyxZQUFFLENBQUMsV0FBVyxjQUFjLENBQUM7QUFHaEQscUJBQXFCO0FBQ3JCLG1CQUFtQjtBQUNuQixJQUFJO0FBQ0osTUFBTSxNQUFNO0lBQ1IsWUFBNEIsSUFBWSxFQUFVLElBQVU7UUFBaEMsU0FBSSxHQUFKLElBQUksQ0FBUTtRQUFVLFNBQUksR0FBSixJQUFJLENBQU07SUFDNUQsQ0FBQztJQUVELElBQVcsSUFBSSxDQUFDLEtBQWE7UUFDekIsSUFBSSxDQUFDLElBQUksQ0FBQyxLQUFLLEdBQUcsSUFBSSxDQUFDLElBQUksQ0FBQyxLQUFLLENBQUMsS0FBSyxDQUFDLFVBQVUsQ0FBQyxDQUFDLEdBQUcsQ0FBQyxDQUFDLEtBQWEsRUFBRSxFQUFFLENBQ3JFLEtBQUssQ0FBQyxLQUFLLENBQUMsNEJBQTRCLENBQUMsQ0FBQyxDQUFDLENBQUMsUUFBUSxLQUFLLEVBQUUsQ0FBQyxDQUFDLENBQUMsS0FBSyxDQUFDLENBQUMsSUFBSSxDQUFDLEdBQUcsQ0FBQyxDQUFDO0lBQ3hGLENBQUM7Q0FDSjtBQUdELE1BQU0sS0FBSztJQUlQLFlBQTRCLElBQVksRUFBVSxHQUFRO1FBQTlCLFNBQUksR0FBSixJQUFJLENBQVE7UUFBVSxRQUFHLEdBQUgsR0FBRyxDQUFLO1FBQ3RELE1BQU0sUUFBUSxHQUFhLEVBQUUsQ0FBQztRQUM5QixLQUFLLE1BQU0sRUFBQyxDQUFDLEVBQUMsSUFBSSxHQUFHLENBQUMsR0FBRyxDQUFDLENBQUMsQ0FBQyxDQUFDLENBQUMsQ0FBQyxJQUFJLEVBQUU7WUFDakMsTUFBTSxFQUFDLEVBQUUsRUFB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