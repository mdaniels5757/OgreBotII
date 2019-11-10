import cheerio from "cheerio";
import { HttpFetch } from './../http';
import { Party } from './types';
import { matchAll } from '../stringUtils';

export class VirginiaElectionTracker {

    constructor(private context: string) {       
    } 

    isEligible(stateName: string): boolean {
        return stateName.toLocaleLowerCase() === "virginia";
    }
    
    async getCountyTotals(): Promise<{[x in string] : {[x in Party]: number}}> {
        const baseUrl = `https://historical.elections.virginia.gov/elections/view/${this.context}`;

        const httpFetch = new HttpFetch();
        const $ = cheerio.load(await httpFetch.fetch(baseUrl));

        const candidatesIndices = $(".precinct_data thead th").get().map(block => {
            const $block = $(block);
            if ($block.hasClass("democratic_party")) {
                return "d";
            } else if ($block.hasClass("republican_party")) {
                return "r";
            } else if ($block.hasClass("candidate_key_reference")) {
                return "i";
            } else if ($block.html() === "All Others") {
                return "i";
            }
        }).slice(2);
        
        return Object.fromEntries(function*(){
            for (const tr of $("tr.m_item").get()) {
                const locality =  $("td.locality a", tr).eq(0).text().replace(/ county$/i, "");
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
                yield [locality, results];
            }
        }());
    }

}