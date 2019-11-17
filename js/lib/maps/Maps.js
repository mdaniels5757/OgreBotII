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
    //normalize common abbreviations
    static normalize(state, county) {
        return ((state, county) => {
            switch (state) {
                case "louisiana":
                    switch (county) {
                        case "lasalle":
                            return "la salle";
                    }
                    break;
                case "mississippi":
                    switch (county) {
                        case "jeff davis":
                            return "jefferson davis";
                    }
            }
            return county;
        })(state.toLowerCase(), county.toLowerCase()).replace(/ /g, "_");
    }
}
class State {
    constructor(name, xml) {
        this.name = name;
        this.xml = xml;
        const counties = [];
        for (const { $ } of xml.svg.g[0].path) {
            const { id, style } = $;
            const [, countyName] = id && style && id.match(/^[A-Z]{2}_([A-Za-z_\.]+)$/) || [];
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
                            const normalizedCounty = County.normalize(stateName, county);
                            const stateCounty = stateCounties[normalizedCounty];
                            if (!stateCounty) {
                                console.error(`County ${county}, ${stateName} not found in SVG`);
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
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiTWFwcy5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbIk1hcHMudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7Ozs7QUFDQSxvREFBMkI7QUFDM0IsK0NBQXVCO0FBQ3ZCLDRDQUFvQjtBQUtwQixNQUFNLE9BQU8sR0FBRyxJQUFJLGdCQUFLLENBQUMsT0FBTyxFQUFFLENBQUM7QUFDcEMsTUFBTSxPQUFPLEdBQUcsR0FBRyxZQUFFLENBQUMsV0FBVyxjQUFjLENBQUM7QUFHaEQscUJBQXFCO0FBQ3JCLG1CQUFtQjtBQUNuQixJQUFJO0FBQ0osTUFBTSxNQUFNO0lBQ1IsWUFBNEIsSUFBWSxFQUFVLElBQVU7UUFBaEMsU0FBSSxHQUFKLElBQUksQ0FBUTtRQUFVLFNBQUksR0FBSixJQUFJLENBQU07SUFDNUQsQ0FBQztJQUVELElBQVcsSUFBSSxDQUFDLEtBQWE7UUFDekIsSUFBSSxDQUFDLElBQUksQ0FBQyxLQUFLLEdBQUcsSUFBSSxDQUFDLElBQUksQ0FBQyxLQUFLLENBQUMsS0FBSyxDQUFDLFVBQVUsQ0FBQyxDQUFDLEdBQUcsQ0FBQyxDQUFDLEtBQWEsRUFBRSxFQUFFLENBQ3JFLEtBQUssQ0FBQyxLQUFLLENBQUMsNEJBQTRCLENBQUMsQ0FBQyxDQUFDLENBQUMsUUFBUSxLQUFLLEVBQUUsQ0FBQyxDQUFDLENBQUMsS0FBSyxDQUFDLENBQUMsSUFBSSxDQUFDLEdBQUcsQ0FBQyxDQUFDO0lBQ3hGLENBQUM7SUFFRCxnQ0FBZ0M7SUFDekIsTUFBTSxDQUFDLFNBQVMsQ0FBQyxLQUFhLEVBQUUsTUFBYztRQUNqRCxPQUFPLENBQUMsQ0FBQyxLQUFLLEVBQUUsTUFBTSxFQUFFLEVBQUU7WUFDdEIsUUFBUSxLQUFLLEVBQUU7Z0JBQ1gsS0FBSyxXQUFXO29CQUNaLFFBQVEsTUFBTSxFQUFFO3dCQUNaLEtBQUssU0FBUzs0QkFDVixPQUFPLFVBQVUsQ0FBQztxQkFDekI7b0JBQ0QsTUFBTTtnQkFDVixLQUFLLGFBQWE7b0JBQ2QsUUFBUSxNQUFN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