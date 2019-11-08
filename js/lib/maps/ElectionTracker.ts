import { Party } from "./types";

export interface ElectionTracker {
    isEligible(stateName: string): boolean;
    getCountyTotals(): Promise<{[x in string] : {[x in Party]: number}}>;
}