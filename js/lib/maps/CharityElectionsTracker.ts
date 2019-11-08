import { HttpFetch } from './../http';
import { Party } from './types';

export class CharityElectionsTracker {

    constructor(private stateName: string, private url: string) {
    } 

    isEligible(stateName: string): boolean {
        return stateName.toLocaleLowerCase() === this.stateName.toLocaleLowerCase();
    }
    
    async getCountyTotals(): Promise<{[x in string] : {[x in Party]: number}}> {
        const totals : {[x in string] : {[x in Party]: number}}= {};
        for (const {A: county, V: [[r, d, ...rest]]} of JSON.parse(await new HttpFetch().fetch(this.url)).Contests) {
            const i = rest.reduce((t: number, n: number) => t + n, 0);
            totals[<string>county] = {r, d, i};
        }
        return totals;
    }


}