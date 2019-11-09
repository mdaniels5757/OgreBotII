import { HttpFetch } from './../http';
import { Party } from './types';
import { matchAll } from '../stringUtils';

export class PoliticoElectionTracker {
    private stateName: string;

    constructor(private context: string) {
        this.stateName = this.context.match(/\/(.+?)\/?$/)![1];        
    } 

    isEligible(stateName: string): boolean {
        return stateName.toLocaleLowerCase() === this.stateName.toLocaleLowerCase();
    }
    
    async getCountyTotals(): Promise<{[x in string] : {[x in Party]: number}}> {
        const baseUrl = `https://www.politico.com/election-results/${this.context.substring(0, 4)}/data/${this.context}`;

        const httpFetch = new HttpFetch();
        const countiesPromise = httpFetch.fetch(`${baseUrl}/context.json`).then(json =>
            Object.fromEntries(function* () {
                for (const county of JSON.parse(json).division.children) {
                    const match = county.label.match(/(.+?) County\, [A-Z ]+$/i);
                    if (match) {
                        yield <[number, string]>[+county.code, match[1]];
                    }
                }
            }())
        );

        const totals : {[x in string] : {[x in Party]: number}}= {};

        await httpFetch.fetch(`${baseUrl}/county.csv`).then(async text => {
            const counties = await countiesPromise;
            for (const [, stateCode, partyCode, voteTotal] of matchAll(/^(\d+)\,county\,\w\,(\w+)\,\d+\,\d+\,\d+\,[\d\.]+\,\d+\,\d+\,\w+\,\,\w+\,(\d+)\,/gm, text)) {
                const countyName = counties[+stateCode];
                if (!countyName) {
                    console.warn(`Unrecognized state code: ${stateCode}`);
                    continue;
                }
                const countyTotal = totals[countyName] = totals[countyName] || {};

                const index = partyCode === "GOP" ? 'r' : partyCode === "Dem" ? 'd': 'i';
                countyTotal[index] = +voteTotal + (countyTotal[index] || 0);
            }
        });

        return totals;
    }

}