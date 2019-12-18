"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const sync_1 = __importDefault(require("csv-parse/lib/sync"));
const http_1 = require("../http");
const collectionUtils_1 = require("../collectionUtils");
class IllinoisElectionTracker {
    constructor(year) {
        this.year = year;
    }
    isEligible(stateName) {
        return stateName.toLocaleLowerCase() === "illinois";
    }
    async getCountyTotals() {
        const baseUrl = `https://www.elections.il.gov/DocDisplay.aspx?doc=Downloads/ElectionOperations/VoteTotals/${this.year}/ByCounty/GE${this.year}Cty.txt`;
        const [fields, ...results] = sync_1.default(await (await new http_1.HttpFetch().fetch(baseUrl)).replace(/"/g, ""), { delimiter: '\t' });
        const [officeIndex, partyIndex, countyIndex, votesIndex] = collectionUtils_1.arrayFindAll(fields, "OfficeName", "PartyName", "County", "Votes");
        const counties = {};
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
                (counties[countyName] || (counties[countyName] = { r: 0, d: 0, i: 0 }))[partyName] = votesCount;
            }
        }
        return counties;
    }
}
exports.IllinoisElectionTracker = IllinoisElectionTracker;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiSWxsaW5vaXNFbGVjdGlvblRyYWNrZXIuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJJbGxpbm9pc0VsZWN0aW9uVHJhY2tlci50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7OztBQUFBLDhEQUEwQztBQUMxQyxrQ0FBb0M7QUFFcEMsd0RBQWtEO0FBRWxELE1BQWEsdUJBQXVCO0lBRWhDLFlBQW9CLElBQVk7UUFBWixTQUFJLEdBQUosSUFBSSxDQUFRO0lBQ2hDLENBQUM7SUFFRCxVQUFVLENBQUMsU0FBaUI7UUFDeEIsT0FBTyxTQUFTLENBQUMsaUJBQWlCLEVBQUUsS0FBSyxVQUFVLENBQUM7SUFDeEQsQ0FBQztJQUVELEtBQUssQ0FBQyxlQUFlO1FBQ2pCLE1BQU0sT0FBTyxHQUFHLDRGQUE0RixJQUFJLENBQUMsSUFBSSxlQUFlLElBQUksQ0FBQyxJQUFJLFNBQVMsQ0FBQztRQUV2SixNQUFNLENBQUMsTUFBTSxFQUFFLEdBQUcsT0FBTyxDQUFDLEdBQWdCLGNBQVEsQ0FBQyxNQUFNLENBQUMsTUFBTSxJQUFJLGdCQUFTLEVBQUUsQ0FBQyxLQUFLLENBQUMsT0FBTyxDQUFDLENBQUMsQ0FBQyxPQUFPLENBQUMsSUFBSSxFQUFFLEVBQUUsQ0FBQyxFQUFFLEVBQUMsU0FBUyxFQUFFLElBQUksRUFBQyxDQUFDLENBQUM7UUFFdEksTUFBTSxDQUFDLFdBQVcsRUFBRSxVQUFVLEVBQUUsV0FBVyxFQUFFLFVBQVUsQ0FBQyxHQUFHLDhCQUFZLENBQUMsTUFBTSxFQUFFLFlBQVksRUFBRSxXQUFXLEVBQUUsUUFBUSxFQUFFLE9BQU8sQ0FBQyxDQUFDO1FBRzlILE1BQU0sUUFBUSxHQUE2QyxFQUFFLENBQUM7UUFFOUQsS0FBSyxNQUFNLE1BQU0sSUFBSSxPQUFPLEVBQUU7WUFDMUIsSUFBSSxNQUFNLENBQUMsV0FBVyxDQUFDLEtBQUssOEJBQThCLEVBQUU7Z0JBQ3hELE1BQU0sVUFBVSxHQUFHLE1BQU0sQ0FBQyxXQUFXLENBQUMsQ0FBQztnQkFDdkMsTUFBTSxTQUFTLEdBQUcsQ0FBQyxHQUFHLEVBQUU7b0JBQ3BCLFFBQVEsTUFBTSxDQUFDLFVBQVUsQ0FBQyxFQUFFO3dCQUN4QixLQUFLLFlBQVk7NEJBQ2IsT0FBTyxHQUFHLENBQUM7d0JBQ2YsS0FBSyxZQUFZOzRCQUNiLE9BQU8sR0FBRyxDQUFDO3dCQUNmOzRCQUNJLE9BQU8sR0FBRyxDQUFDO3FCQUNsQjtnQkFDTCxDQUFDLENBQUMsRUFBRSxDQUFDO2dCQUNMLE1BQU0sVUFBVSxHQUFHLENBQUMsTUFBTSxDQUFDLFVBQVUsQ0FBQyxDQUFDO2dCQUV2QyxDQUFDLFFBQVEsQ0FBQyxVQUFVLENBQUMsSUFBSSxDQUFDLFFBQVEsQ0FBQyxVQUFVLENBQUMsR0FBRyxFQUFDLENBQUMsRUFBRSxDQUFDLEVBQUUsQ0FBQyxFQUFFLENBQUMsRUFBRSxDQUFDLEVBQUUsQ0FBQyxFQUFDLENBQUMsQ0FBQyxDQUFDLFNBQVMsQ0FBQyxHQUFHLFVBQVUsQ0FBQzthQUNqRztTQUNKO1FBQ0QsT0FBTyxRQUFRLENBQUM7SUFDcEIsQ0FBQztDQUVKO0FBeENELDBEQXdDQyJ9