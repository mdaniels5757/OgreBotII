import cheerio from "cheerio";
import { Party } from './types';
import fs from "fs";

//robots.txt denies automatic scraping, so download and parse by hand
export class ClarionElectionsTracker {

    constructor(private file: string, private state: string) {
    }

    isEligible(stateName: string): boolean {
        return stateName.toLocaleLowerCase() === this.state.toLocaleLowerCase();
    }

    async getCountyTotals(): Promise<{ [x in string]: { [x in Party]: number } }> {

        const $ = cheerio.load(fs.readFileSync(this.file).toString());
        const table = $(".panel-body table");

        const candidatesIndices: (Party | undefined)[] = [];
        var i = 0;
        for (const td of $("td", $("thead tr", table)[0]).get()) {
            const $td = $(td);
            const text = $td.text();
            candidatesIndices[i] = (([, party]) => {
                if (!party) {
                    return;
                }
                switch (party.toUpperCase()) {
                    case "GOP":
                        return "r";
                    case "DEM":
                        return "d";
                    default:
                        return "i";
                }
            })((text.match(/\((.+?)\)\s*$/) || []));

            i += +$td.attr("colspan") || 1;
        }

        return Object.fromEntries($("tbody tr").get().slice(1).map(tr => {
            const locality = $("td", tr).eq(0).text().trim();
            const results = {
                r: 0,
                d: 0,
                i: 0
            };
            $("td", tr).each((index, td) => {
                const indexParty = candidatesIndices[index];
                if (indexParty) {
                    results[indexParty] += +$(td).text().replace(/\D+/, "");
                }
            });
            return [locality, results];
        }));
    }
}