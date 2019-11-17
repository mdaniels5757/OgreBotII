import { cachable } from './../decorators/cachable';
import cheerio from "cheerio";
import csvParse from "csv-parse/lib/sync";
import { HttpFetch } from '../http';
import { Party } from './types';

export class LouisianaElectionTracker {

    constructor(private context: string) { 
    } 

    isEligible(stateName: string): boolean {
        return stateName.toLocaleLowerCase() === "louisiana";
    }
    
    async getCountyTotals(): Promise<{[x in string] : {[x in Party]: number}}> {
        const baseUrl = `https://voterportal.sos.la.gov/ElectionResults/ElectionResults/Data?blob=${this.context}`;

        const [fields, ...results]  = <string[][]>csvParse(await new HttpFetch().fetch(baseUrl)));

        const parishIndex = fields.findIndex(field => field === "Parish");
        if (parishIndex < 0) {
            throw new Error(`No parish field found: ${JSON.stringify(fields)}`)
        }
        const candidatesIndices = fields.map(field => {
            const [, party] = field.match(/\((.+?)\)\s*$/) || [];
            if (!party) {
                return;
            }
            switch (party.toUpperCase()) {
                case "REP":
                    return "r";
                case "DEM":
                    return "d";
                default:
                    return "i";
            }
        });
        
        return Object.fromEntries(results.map(row => {
            const results = {
                r: 0,
                d: 0,
                i: 0
            };

            row.forEach((entry, index) => {
                const indexParty = candidatesIndices[index];
                if (indexParty) {
                    results[indexParty] += +entry;
                }
            });
            return [row[parishIndex], results];
        }));
    }

}