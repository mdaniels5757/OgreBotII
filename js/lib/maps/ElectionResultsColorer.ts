import { PartyOrTie } from "./types";

const allColorsUsed = new Set<string>();

export type ElectionResultColorer = (party: PartyOrTie, percent: number) => string;

const electionColors = {
    d: {
        0: "#ffabc5",
        1: "#ffabc5",
        2: "#ffabc5",
        3: "#ffabc5",
        4: "#a5b0ff",
        5: "#7996e2",
        6: "#6674de",
        7: "#584cde",
        8: "#3933e5",
        9: "#0D0596",
        10: "#0D0596"
    } as const,
    r: {
        0: "#ffb2b2",
        1: "#ffb2b2",
        2: "#ffb2b2",
        3: "#ffb2b2",
        4: "#ffb2b2",
        5: "#e27f7f",
        6: "#d75d5d",
        7: "#d72f30",
        8: "#c21b18",
        9: "#a80000",
        10: "#a80000"
    } as const,
    t: {
        0: "#888",
        1: "#888",
        2: "#888",
        3: "#888",
        4: "#888",
        5: "#888",
        6: "#888",
        7: "#888",
        8: "#888",
        9: "#888",
        10: "#888"
    } as const,
    i: {
        0: "",
        1: "",
        2: "",
        3: "",
        4: "",
        5: "",
        6: "",
        7: "",
        8: "",
        9: "",
        10: ""
    } as const
} as const;

export const standardElectionResultColorer = (party: PartyOrTie, percent: number) => {
    const roundedPercent = <0 | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9 | 10>Math.floor(percent * 10);
    const color = electionColors[party][roundedPercent];
    
    if (!allColorsUsed.has(`${party}${roundedPercent}`)) {
        console.log(`${party}${roundedPercent}0: ${color}`);
        allColorsUsed.add(`${party}${roundedPercent}`);
    }
    return color;
}