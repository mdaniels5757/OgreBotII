"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const http_1 = require("./../http");
class CharityElectionsTracker {
    constructor(stateName, url) {
        this.stateName = stateName;
        this.url = url;
    }
    isEligible(stateName) {
        return stateName.toLocaleLowerCase() === this.stateName.toLocaleLowerCase();
    }
    async getCountyTotals() {
        const totals = {};
        for (const { A: county, V: [[r, d, ...rest]] } of JSON.parse(await new http_1.HttpFetch().fetch(this.url)).Contests) {
            const i = rest.reduce((t, n) => t + n, 0);
            totals[county] = { r, d, i };
        }
        return totals;
    }
}
exports.CharityElectionsTracker = CharityElectionsTracker;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiQ2hhcml0eUVsZWN0aW9uc1RyYWNrZXIuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJDaGFyaXR5RWxlY3Rpb25zVHJhY2tlci50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOztBQUFBLG9DQUFzQztBQUd0QyxNQUFhLHVCQUF1QjtJQUVoQyxZQUFvQixTQUFpQixFQUFVLEdBQVc7UUFBdEMsY0FBUyxHQUFULFNBQVMsQ0FBUTtRQUFVLFFBQUcsR0FBSCxHQUFHLENBQVE7SUFDMUQsQ0FBQztJQUVELFVBQVUsQ0FBQyxTQUFpQjtRQUN4QixPQUFPLFNBQVMsQ0FBQyxpQkFBaUIsRUFBRSxLQUFLLElBQUksQ0FBQyxTQUFTLENBQUMsaUJBQWlCLEVBQUUsQ0FBQztJQUNoRixDQUFDO0lBRUQsS0FBSyxDQUFDLGVBQWU7UUFDakIsTUFBTSxNQUFNLEdBQTZDLEVBQUUsQ0FBQztRQUM1RCxLQUFLLE1BQU0sRUFBQyxDQUFDLEVBQUUsTUFBTSxFQUFFLENBQUMsRUFBRSxDQUFDLENBQUMsQ0FBQyxFQUFFLENBQUMsRUFBRSxHQUFHLElBQUksQ0FBQyxDQUFDLEVBQUMsSUFBSSxJQUFJLENBQUMsS0FBSyxDQUFDLE1BQU0sSUFBSSxnQkFBUyxFQUFFLENBQUMsS0FBSyxDQUFDLElBQUksQ0FBQyxHQUFHLENBQUMsQ0FBQyxDQUFDLFFBQVEsRUFBRTtZQUN4RyxNQUFNLENBQUMsR0FBRyxJQUFJLENBQUMsTUFBTSxDQUFDLENBQUMsQ0FBUyxFQUFFLENBQVMsRUFBRSxFQUFFLENBQUMsQ0FBQyxHQUFHLENBQUMsRUFBRSxDQUFDLENBQUMsQ0FBQztZQUMxRCxNQUFNLENBQVMsTUFBTSxDQUFDLEdBQUcsRUFBQyxDQUFDLEVBQUUsQ0FBQyxFQUFFLENBQUMsRUFBQyxDQUFDO1NBQ3RDO1FBQ0QsT0FBTyxNQUFNLENBQUM7SUFDbEIsQ0FBQztDQUdKO0FBbkJELDBEQW1CQyJ9