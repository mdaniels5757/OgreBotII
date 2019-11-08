import { PartyOrTie } from "./types";

export type ElectionResultColorer = (party: PartyOrTie, percent: number) => string;

export const standardElectionResultColorer = (party: PartyOrTie, percent: number) => {
    switch (party) {
        case "d":
            switch (Math.floor(percent * 10)) {
                case 0:
                case 1:
                case 2:
                case 3:
                case 4:
                    return "#a5b0ff";
                case 5:
                    return "#7996e2";
                case 6:
                    return "#6674de";
                case 7:
                    return "#584cde";
                default:
                    return "#3933e5";
            }
        case "r":
            switch (Math.floor(percent * 10)) {
                case 0:
                case 1:
                case 2:
                case 3:
                case 4:
                    return "#ffb2b2";
                case 5:
                    return "#e27f7f";
                case 6:
                    return "#d75d5d";
                case 7:
                    return "#d72f30";
                case 8:
                    return "#c21b18";
                default:
                    return "#a80000";
            }
        case "t":
            return "#888";
        default:
            throw new Error("Can't currently handle independents");
    }
}