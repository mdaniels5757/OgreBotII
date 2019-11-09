"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const http_1 = require("./../http");
const stringUtils_1 = require("../stringUtils");
class PoliticoElectionTracker {
    constructor(context) {
        this.context = context;
        this.stateName = this.context.match(/\/(.+?)\/?$/)[1];
    }
    isEligible(stateName) {
        return stateName.toLocaleLowerCase() === this.stateName.toLocaleLowerCase();
    }
    async getCountyTotals() {
        const baseUrl = `https://www.politico.com/election-results/${this.context.substring(0, 4)}/data/${this.context}`;
        const httpFetch = new http_1.HttpFetch();
        const countiesPromise = httpFetch.fetch(`${baseUrl}/context.json`).then(json => Object.fromEntries(function* () {
            for (const county of JSON.parse(json).division.children) {
                const match = county.label.match(/(.+?) County\, [A-Z ]+$/i);
                if (match) {
                    yield [+county.code, match[1]];
                }
            }
        }()));
        const totals = {};
        await httpFetch.fetch(`${baseUrl}/county.csv`).then(async (text) => {
            const counties = await countiesPromise;
            for (const [, stateCode, partyCode, voteTotal] of stringUtils_1.matchAll(/^(\d+)\,county\,\w\,(\w+)\,\d+\,\d+\,\d+\,[\d\.]+\,\d+\,\d+\,\w+\,\,\w+\,(\d+)\,/gm, text)) {
                const countyName = counties[+stateCode];
                if (!countyName) {
                    console.warn(`Unrecognized state code: ${stateCode}`);
                    continue;
                }
                const countyTotal = totals[countyName] = totals[countyName] || {};
                const index = partyCode === "GOP" ? 'r' : partyCode === "Dem" ? 'd' : 'i';
                countyTotal[index] = +voteTotal + (countyTotal[index] || 0);
            }
        });
        return totals;
    }
}
exports.PoliticoElectionTracker = PoliticoElectionTracker;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiUG9saXRpY29FbGVjdGlvblRyYWNrZXIuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJQb2xpdGljb0VsZWN0aW9uVHJhY2tlci50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOztBQUFBLG9DQUFzQztBQUV0QyxnREFBMEM7QUFFMUMsTUFBYSx1QkFBdUI7SUFHaEMsWUFBb0IsT0FBZTtRQUFmLFlBQU8sR0FBUCxPQUFPLENBQVE7UUFDL0IsSUFBSSxDQUFDLFNBQVMsR0FBRyxJQUFJLENBQUMsT0FBTyxDQUFDLEtBQUssQ0FBQyxhQUFhLENBQUUsQ0FBQyxDQUFDLENBQUMsQ0FBQztJQUMzRCxDQUFDO0lBRUQsVUFBVSxDQUFDLFNBQWlCO1FBQ3hCLE9BQU8sU0FBUyxDQUFDLGlCQUFpQixFQUFFLEtBQUssSUFBSSxDQUFDLFNBQVMsQ0FBQyxpQkFBaUIsRUFBRSxDQUFDO0lBQ2hGLENBQUM7SUFFRCxLQUFLLENBQUMsZUFBZTtRQUNqQixNQUFNLE9BQU8sR0FBRyw2Q0FBNkMsSUFBSSxDQUFDLE9BQU8sQ0FBQyxTQUFTLENBQUMsQ0FBQyxFQUFFLENBQUMsQ0FBQyxTQUFTLElBQUksQ0FBQyxPQUFPLEVBQUUsQ0FBQztRQUVqSCxNQUFNLFNBQVMsR0FBRyxJQUFJLGdCQUFTLEVBQUUsQ0FBQztRQUNsQyxNQUFNLGVBQWUsR0FBRyxTQUFTLENBQUMsS0FBSyxDQUFDLEdBQUcsT0FBTyxlQUFlLENBQUMsQ0FBQyxJQUFJLENBQUMsSUFBSSxDQUFDLEVBQUUsQ0FDM0UsTUFBTSxDQUFDLFdBQVcsQ0FBQyxRQUFRLENBQUM7WUFDeEIsS0FBSyxNQUFNLE1BQU0sSUFBSSxJQUFJLENBQUMsS0FBSyxDQUFDLElBQUksQ0FBQyxDQUFDLFFBQVEsQ0FBQyxRQUFRLEVBQUU7Z0JBQ3JELE1BQU0sS0FBSyxHQUFHLE1BQU0sQ0FBQyxLQUFLLENBQUMsS0FBSyxDQUFDLDBCQUEwQixDQUFDLENBQUM7Z0JBQzdELElBQUksS0FBSyxFQUFFO29CQUNQLE1BQXdCLENBQUMsQ0FBQyxNQUFNLENBQUMsSUFBSSxFQUFFLEtBQUssQ0FBQyxDQUFDLENBQUMsQ0FBQyxDQUFDO2lCQUNwRDthQUNKO1FBQ0wsQ0FBQyxFQUFFLENBQUMsQ0FDUCxDQUFDO1FBRUYsTUFBTSxNQUFNLEdBQTZDLEVBQUUsQ0FBQztRQUU1RCxNQUFNLFNBQVMsQ0FBQyxLQUFLLENBQUMsR0FBRyxPQUFPLGFBQWEsQ0FBQyxDQUFDLElBQUksQ0FBQyxLQUFLLEVBQUMsSUFBSSxFQUFDLEVBQUU7WUFDN0QsTUFBTSxRQUFRLEdBQUcsTUFBTSxlQUFlLENBQUM7WUFDdkMsS0FBSyxNQUFNLENBQUMsRUFBRSxTQUFTLEVBQUUsU0FBUyxFQUFFLFNBQVMsQ0FBQyxJQUFJLHNCQUFRLENBQUMsb0ZBQW9GLEVBQUUsSUFBSSxDQUFDLEVBQUU7Z0JBQ3BKLE1BQU0sVUFBVSxHQUFHLFFBQVEsQ0FBQyxDQUFDLFNBQVMsQ0FBQyxDQUFDO2dCQUN4QyxJQUFJLENBQUMsVUFBVSxFQUFFO29CQUNiLE9BQU8sQ0FBQyxJQUFJLENBQUMsNEJBQTRCLFNBQVMsRUFBRSxDQUFDLENBQUM7b0JBQ3RELFNBQVM7aUJBQ1o7Z0JBQ0QsTUFBTSxXQUFXLEdBQUcsTUFBTSxDQUFDLFVBQVUsQ0FBQyxHQUFHLE1BQU0sQ0FBQyxVQUFVLENBQUMsSUFBSSxFQUFFLENBQUM7Z0JBRWxFLE1BQU0sS0FBSyxHQUFHLFNBQVMsS0FBSyxLQUFLLENBQUMsQ0FBQyxDQUFDLEdBQUcsQ0FBQyxDQUFDLENBQUMsU0FBUyxLQUFLLEtBQUssQ0FBQyxDQUFDLENBQUMsR0FBRyxDQUFBLENBQUMsQ0FBQyxHQUFHLENBQUM7Z0JBQ3pFLFdBQVcsQ0FBQyxLQUFLLENBQUMsR0FBRyxDQUFDLFNBQVMsR0FBRyxDQUFDLFdBQVcsQ0FBQyxLQUFLLENBQUMsSUFBSSxDQUFDLENBQUMsQ0FBQzthQUMvRDtRQUNMLENBQUMsQ0FBQyxDQUFDO1FBRUgsT0FBTyxNQUFNLENBQUM7SUFDbEIsQ0FBQztDQUVKO0FBOUNELDBEQThDQyJ9