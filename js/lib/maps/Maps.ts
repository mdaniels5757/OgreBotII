
import xmljs from "xml2js";
import Io from "../io";
import fs from "fs";
import { ElectionTracker } from "./ElectionTracker";
import { PartyOrTie } from "./types";
import { ElectionResultColorer } from "./ElectionResultsColorer";
const builder = new xmljs.Builder();
const mapsDir = `${Io.PROJECT_DIR}/county-maps`;


// enum County_Type {
//     COUNTY, CITY
// }
class County {
    constructor(public readonly name: string, private path : any) {
    }

    public set fill(color: string) {
        this.path.style = this.path.style.split(/\s*;\s*/g).map((style: string) =>
             style.match(/^\s*fill\s*:\s*\#?\w+\s*$/i) ? `fill:${color}` : style).join(";");
    }
}


class State {

    public readonly counties: County[];

    constructor(public readonly name: string, private xml: any) {
        const counties: County[] = [];
        for (const {$} of xml.svg.g[0].path) {
            const {id, style} = <{[x: string] : string}>$;
            const [, countyName] = id && style && id.match(/^[A-Z]{2}_([A-Za-z_]+)$/) || [];
            countyName && counties.push(new County(countyName, $));
        }
        this.counties = Object.seal(counties);
    }

    async write(dest: string) {
        await fs.writeFileSync(dest, builder.buildObject(this.xml));
    }
}

export class StatesBuilder {
    private electionTrackers: ElectionTracker[] = [];
    private electionResultColorer: ElectionResultColorer | undefined;

    addElectionTrackers(...electionTrackers: ElectionTracker[]): this {
        this.electionTrackers.push(...electionTrackers);
        return this;
    }

    setElectionResultHandler(electionResultColorer: ElectionResultColorer): this {
        this.electionResultColorer = electionResultColorer;
        return this;
    }

    async build(dest: string): Promise<any> {
        var {electionResultColorer, electionTrackers} = this;
        if (!electionResultColorer || !electionTrackers[0]) {
            throw new Error("build() called before ready");
        }

        const promises = function*(){
            for (const file of Io.readDir(mapsDir)) {
                const path = `${mapsDir}/${file}`;
                const stateName = file.replace(/\.svg$/, "");
                const tracker = electionTrackers.find(tracker => tracker.isEligible(stateName));
                if (tracker) {
                    yield (async() => {
                        const text = fs.readFileSync(path).toString();
                        const xml = await xmljs.parseStringPromise(text);
                        const state = new State(stateName, xml);

                        const stateCounties: { [x: string]: County } = Object.fromEntries(state.counties.map(county => [county.name.toLowerCase(), county]));
                        for (const [county, {r, d, i}] of Object.entries(await tracker.getCountyTotals())) {
                            const normalizedCounty = county.toLowerCase().replace(/ /g, "_");
                            const stateCounty = stateCounties[normalizedCounty];
                            if (!stateCounty) {
                                console.error(`County ${county}, ${state.name} not found in SVG`);
                                continue;
                            }

                            delete stateCounties[normalizedCounty];

                            const total = r + d + i;
                            stateCounty.fill = electionResultColorer(...((): [PartyOrTie, number] => {
                                if (r > d && r > i) {
                                    return ["r", r / total];
                                } else if (d > r && d > i) {
                                    return ["d", d / total];
                                } else if (i > r && i > d) {
                                    return ["i", d / total];
                                } else {
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
