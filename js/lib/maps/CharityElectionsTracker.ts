import { HttpFetch } from './../http';
import { Party } from './types';

export class CharityElectionsTracker {

    constructor(private stateName: string, private url: string, private electionIndex = 0) {
    } 

    isEligible(stateName: string): boolean {
        return stateName.toLocaleLowerCase() === this.stateName.toLocaleLowerCase();
    }
    
    async getCountyTotals(): Promise<{[x in string] : {[x in Party]: number}}> {
        const totals : {[x in string] : {[x in Party]: number}}= {};
        for (const {A: county, V} of JSON.parse(await new HttpFetch().fetch(this.url)).Contests) {
            const [r, d, ...rest] = V[this.electionIndex];
            const i = rest.reduce((t: number, n: number) => t + n, 0);
            totals[<string>county] = {r, d, i};
        }
        return totals;
    }


}