"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const cheerio_1 = __importDefault(require("cheerio"));
const http_1 = require("./../http");
class VirginiaElectionTracker {
    constructor(context) {
        this.context = context;
    }
    isEligible(stateName) {
        return stateName.toLocaleLowerCase() === "virginia";
    }
    async getCountyTotals() {
        const baseUrl = `https://historical.elections.virginia.gov/elections/view/${this.context}`;
        const httpFetch = new http_1.HttpFetch();
        const $ = cheerio_1.default.load(await httpFetch.fetch(baseUrl));
        const candidatesIndices = $(".precinct_data thead th").get().map(block => {
            const $block = $(block);
            if ($block.hasClass("democratic_party")) {
                return "d";
            }
            else if ($block.hasClass("republican_party")) {
                return "r";
            }
            else if ($block.hasClass("candidate_key_reference")) {
                return "i";
            }
            else if ($block.html() === "All Others") {
                return "i";
            }
        }).slice(2);
        return Object.fromEntries($("tr.m_item").get().map(tr => {
            const locality = $("td.locality a", tr).eq(0).text().replace(/ county$/i, "");
            const results = {
                r: 0,
                d: 0,
                i: 0
            };
            $("td", tr).each((index, td) => {
                const indexParty = candidatesIndices[index];
                if (indexParty) {
                    results[indexParty] += +$(td).text().replace(/\D+/, "");
                }
            });
            return [locality, results];
        }));
    }
}
exports.VirginiaElectionTracker = VirginiaElectionTracker;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiVmlyZ2luaWFFbGVjdGlvblRyYWNrZXIuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJWaXJnaW5pYUVsZWN0aW9uVHJhY2tlci50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7OztBQUFBLHNEQUE4QjtBQUM5QixvQ0FBc0M7QUFHdEMsTUFBYSx1QkFBdUI7SUFFaEMsWUFBb0IsT0FBZTtRQUFmLFlBQU8sR0FBUCxPQUFPLENBQVE7SUFDbkMsQ0FBQztJQUVELFVBQVUsQ0FBQyxTQUFpQjtRQUN4QixPQUFPLFNBQVMsQ0FBQyxpQkFBaUIsRUFBRSxLQUFLLFVBQVUsQ0FBQztJQUN4RCxDQUFDO0lBRUQsS0FBSyxDQUFDLGVBQWU7UUFDakIsTUFBTSxPQUFPLEdBQUcsNERBQTRELElBQUksQ0FBQyxPQUFPLEVBQUUsQ0FBQztRQUUzRixNQUFNLFNBQVMsR0FBRyxJQUFJLGdCQUFTLEVBQUUsQ0FBQztRQUNsQyxNQUFNLENBQUMsR0FBRyxpQkFBTyxDQUFDLElBQUksQ0FBQyxNQUFNLFNBQVMsQ0FBQyxLQUFLLENBQUMsT0FBTyxDQUFDLENBQUMsQ0FBQztRQUV2RCxNQUFNLGlCQUFpQixHQUFHLENBQUMsQ0FBQyx5QkFBeUIsQ0FBQyxDQUFDLEdBQUcsRUFBRSxDQUFDLEdBQUcsQ0FBQyxLQUFLLENBQUMsRUFBRTtZQUNyRSxNQUFNLE1BQU0sR0FBRyxDQUFDLENBQUMsS0FBSyxDQUFDLENBQUM7WUFDeEIsSUFBSSxNQUFNLENBQUMsUUFBUSxDQUFDLGtCQUFrQixDQUFDLEVBQUU7Z0JBQ3JDLE9BQU8sR0FBRyxDQUFDO2FBQ2Q7aUJBQU0sSUFBSSxNQUFNLENBQUMsUUFBUSxDQUFDLGtCQUFrQixDQUFDLEVBQUU7Z0JBQzVDLE9BQU8sR0FBRyxDQUFDO2FBQ2Q7aUJBQU0sSUFBSSxNQUFNLENBQUMsUUFBUSxDQUFDLHlCQUF5QixDQUFDLEVBQUU7Z0JBQ25ELE9BQU8sR0FBRyxDQUFDO2FBQ2Q7aUJBQU0sSUFBSSxNQUFNLENBQUMsSUFBSSxFQUFFLEtBQUssWUFBWSxFQUFFO2dCQUN2QyxPQUFPLEdBQUcsQ0FBQzthQUNkO1FBQ0wsQ0FBQyxDQUFDLENBQUMsS0FBSyxDQUFDLENBQUMsQ0FBQyxDQUFDO1FBRVosT0FBTyxNQUFNLENBQUMsV0FBVyxDQUFDLENBQUMsQ0FBQyxXQUFXLENBQUMsQ0FBQyxHQUFHLEVBQUUsQ0FBQyxHQUFHLENBQUMsRUFBRSxDQUFDLEVBQUU7WUFDcEQsTUFBTSxRQUFRLEdBQUksQ0FBQyxDQUFDLGVBQWUsRUFBRSxFQUFFLENBQUMsQ0FBQyxFQUFFLENBQUMsQ0FBQyxDQUFDLENBQUMsSUFBSSxFQUFFLENBQUMsT0FBTyxDQUFDLFdBQVcsRUFBRSxFQUFFLENBQUMsQ0FBQztZQUMvRSxNQUFNLE9BQU8sR0FBRztnQkFDWixDQUFDLEVBQUUsQ0FBQztnQkFDSixDQUFDLEVBQUUsQ0FBQztnQkFDSixDQUFDLEVBQUUsQ0FBQzthQUNQLENBQUM7WUFDRixDQUFDLENBQUMsSUFBSSxFQUFFLEVBQUUsQ0FBQyxDQUFDLElBQUksQ0FBQyxDQUFDLEtBQUssRUFBRSxFQUFFLEVBQUUsRUFBRTtnQkFDM0IsTUFBTSxVQUFVLEdBQUcsaUJBQWlCLENBQUMsS0FBSyxDQUFDLENBQUM7Z0JBQzVDLElBQUksVUFBVSxFQUFFO29CQUNaLE9BQU8sQ0FBQyxVQUFVLENBQUMsSUFBSSxDQUFDLENBQUMsQ0FBQyxFQUFFLENBQUMsQ0FBQyxJQUFJLEVBQUUsQ0FBQyxPQUFPLENBQUMsS0FBSyxFQUFFLEVBQUUsQ0FBQyxDQUFDO2lCQUMzRDtZQUNMLENBQUMsQ0FBQyxDQUFDO1lBQ0gsT0FBTyxDQUFDLFFBQVEsRUFBRSxPQUFPLENBQUMsQ0FBQztRQUMvQixDQUFDLENBQUMsQ0FBQyxDQUFDO0lBQ1IsQ0FBQztDQUVKO0FBN0NELDBEQTZDQyJ9