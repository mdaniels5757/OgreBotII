import csvParse from "csv-parse/lib/sync";
import { HttpFetch } from '../http';
import { Party } from './types';
import { arrayFindAll } from "../collectionUtils";

export class IllinoisElectionTracker {

    constructor(private year: number) { 
    } 

    isEligible(stateName: string): boolean {
        return stateName.toLocaleLowerCase() === "illinois";
    }
    
    async getCountyTotals(): Promise<{[x in string] : {[x in Party]: number}}> {
        const baseUrl = `https://www.elections.il.gov/DocDisplay.aspx?doc=Downloads/ElectionOperations/VoteTotals/${this.year}/ByCounty/GE${this.year}Cty.txt`;

        const [fields, ...results]  = <string[][]>csvParse(await (await new HttpFetch().fetch(baseUrl)).replace(/"/g, ""), {delimiter: '\t'});

        const [officeIndex, partyIndex, countyIndex, votesIndex] = arrayFindAll(fields, "OfficeName", "PartyName", "County", "Votes"); 


        const counties: {[x in string] : {[x in Party]: number}} = {};
        
        for (const result of results) {
            if (result[officeIndex] === "PRESIDENT AND VICE PRESIDENT") {
                const countyName = result[countyIndex];
                const partyName = (() => {
                    switch (result[partyIndex]) {
                        case "REPUBLICAN":
                            return "r";
                        case "DEMOCRATIC":
                            return "d";
                        default:
                            return "i";
                    }
                })();
                const votesCount = +result[votesIndex];

                (counties[countyName] || (counties[countyName] = {r: 0, d: 0, i: 0}))[partyName] = votesCount;
            }
        }
        return counties;
    }

}