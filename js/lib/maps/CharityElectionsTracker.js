"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const http_1 = require("./../http");
class CharityElectionsTracker {
    constructor(stateName, url, electionIndex = 0) {
        this.stateName = stateName;
        this.url = url;
        this.electionIndex = electionIndex;
    }
    isEligible(stateName) {
        return stateName.toLocaleLowerCase() === this.stateName.toLocaleLowerCase();
    }
    async getCountyTotals() {
        const totals = {};
        for (const { A: county, V } of JSON.parse(await new http_1.HttpFetch().fetch(this.url)).Contests) {
            const [r, d, ...rest] = V[this.electionIndex];
            const i = rest.reduce((t, n) => t + n, 0);
            totals[county] = { r, d, i };
        }
        return totals;
    }
}
exports.CharityElectionsTracker = CharityElectionsTracker;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiQ2hhcml0eUVsZWN0aW9uc1RyYWNrZXIuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJDaGFyaXR5RWxlY3Rpb25zVHJhY2tlci50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOztBQUFBLG9DQUFzQztBQUd0QyxNQUFhLHVCQUF1QjtJQUVoQyxZQUFvQixTQUFpQixFQUFVLEdBQVcsRUFBVSxnQkFBZ0IsQ0FBQztRQUFqRSxjQUFTLEdBQVQsU0FBUyxDQUFRO1FBQVUsUUFBRyxHQUFILEdBQUcsQ0FBUTtRQUFVLGtCQUFhLEdBQWIsYUFBYSxDQUFJO0lBQ3JGLENBQUM7SUFFRCxVQUFVLENBQUMsU0FBaUI7UUFDeEIsT0FBTyxTQUFTLENBQUMsaUJBQWlCLEVBQUUsS0FBSyxJQUFJLENBQUMsU0FBUyxDQUFDLGlCQUFpQixFQUFFLENBQUM7SUFDaEYsQ0FBQztJQUVELEtBQUssQ0FBQyxlQUFlO1FBQ2pCLE1BQU0sTUFBTSxHQUE2QyxFQUFFLENBQUM7UUFDNUQsS0FBSyxNQUFNLEVBQUMsQ0FBQyxFQUFFLE1BQU0sRUFBRSxDQUFDLEVBQUMsSUFBSSxJQUFJLENBQUMsS0FBSyxDQUFDLE1BQU0sSUFBSSxnQkFBUyxFQUFFLENBQUMsS0FBSyxDQUFDLElBQUksQ0FBQyxHQUFHLENBQUMsQ0FBQyxDQUFDLFFBQVEsRUFBRTtZQUNyRixNQUFNLENBQUMsQ0FBQyxFQUFFLENBQUMsRUFBRSxHQUFHLElBQUksQ0FBQyxHQUFHLENBQUMsQ0FBQyxJQUFJLENBQUMsYUFBYSxDQUFDLENBQUM7WUFDOUMsTUFBTSxDQUFDLEdBQUcsSUFBSSxDQUFDLE1BQU0sQ0FBQyxDQUFDLENBQVMsRUFBRSxDQUFTLEVBQUUsRUFBRSxDQUFDLENBQUMsR0FBRyxDQUFDLEVBQUUsQ0FBQyxDQUFDLENBQUM7WUFDMUQsTUFBTSxDQUFTLE1BQU0sQ0FBQyxHQUFHLEVBQUMsQ0FBQyxFQUFFLENBQUMsRUFBRSxDQUFDLEVBQUMsQ0FBQztTQUN0QztRQUNELE9BQU8sTUFBTSxDQUFDO0lBQ2xCLENBQUM7Q0FHSjtBQXBCRCwwREFvQkMifQ==