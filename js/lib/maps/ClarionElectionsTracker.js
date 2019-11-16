"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const cheerio_1 = __importDefault(require("cheerio"));
const fs_1 = __importDefault(require("fs"));
//robots.txt denies automatic scraping, so download and parse by hand
class ClarionElectionsTracker {
    constructor(file, state) {
        this.file = file;
        this.state = state;
    }
    isEligible(stateName) {
        return stateName.toLocaleLowerCase() === this.state.toLocaleLowerCase();
    }
    async getCountyTotals() {
        const $ = cheerio_1.default.load(fs_1.default.readFileSync(this.file).toString());
        const table = $(".panel-body table");
        const candidatesIndices = [];
        var i = 0;
        for (const td of $("td", $("thead tr", table)[0]).get()) {
            const $td = $(td);
            const text = $td.text();
            candidatesIndices[i] = (([, party]) => {
                if (!party) {
                    return;
                }
                switch (party.toUpperCase()) {
                    case "GOP":
                        return "r";
                    case "DEM":
                        return "d";
                    default:
                        return "i";
                }
            })((text.match(/\((.+?)\)\s*$/) || []));
            i += +$td.attr("colspan") || 1;
        }
        return Object.fromEntries($("tbody tr").get().slice(1).map(tr => {
            const locality = $("td", tr).eq(0).text().trim();
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
exports.ClarionElectionsTracker = ClarionElectionsTracker;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiQ2xhcmlvbkVsZWN0aW9uc1RyYWNrZXIuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJDbGFyaW9uRWxlY3Rpb25zVHJhY2tlci50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7OztBQUFBLHNEQUE4QjtBQUU5Qiw0Q0FBb0I7QUFFcEIscUVBQXFFO0FBQ3JFLE1BQWEsdUJBQXVCO0lBRWhDLFlBQW9CLElBQVksRUFBVSxLQUFhO1FBQW5DLFNBQUksR0FBSixJQUFJLENBQVE7UUFBVSxVQUFLLEdBQUwsS0FBSyxDQUFRO0lBQ3ZELENBQUM7SUFFRCxVQUFVLENBQUMsU0FBaUI7UUFDeEIsT0FBTyxTQUFTLENBQUMsaUJBQWlCLEVBQUUsS0FBSyxJQUFJLENBQUMsS0FBSyxDQUFDLGlCQUFpQixFQUFFLENBQUM7SUFDNUUsQ0FBQztJQUVELEtBQUssQ0FBQyxlQUFlO1FBRWpCLE1BQU0sQ0FBQyxHQUFHLGlCQUFPLENBQUMsSUFBSSxDQUFDLFlBQUUsQ0FBQyxZQUFZLENBQUMsSUFBSSxDQUFDLElBQUksQ0FBQyxDQUFDLFFBQVEsRUFBRSxDQUFDLENBQUM7UUFDOUQsTUFBTSxLQUFLLEdBQUcsQ0FBQyxDQUFDLG1CQUFtQixDQUFDLENBQUM7UUFFckMsTUFBTSxpQkFBaUIsR0FBMEIsRUFBRSxDQUFDO1FBQ3BELElBQUksQ0FBQyxHQUFHLENBQUMsQ0FBQztRQUNWLEtBQUssTUFBTSxFQUFFLElBQUksQ0FBQyxDQUFDLElBQUksRUFBRSxDQUFDLENBQUMsVUFBVSxFQUFFLEtBQUssQ0FBQyxDQUFDLENBQUMsQ0FBQyxDQUFDLENBQUMsR0FBRyxFQUFFLEVBQUU7WUFDckQsTUFBTSxHQUFHLEdBQUcsQ0FBQyxDQUFDLEVBQUUsQ0FBQyxDQUFDO1lBQ2xCLE1BQU0sSUFBSSxHQUFHLEdBQUcsQ0FBQyxJQUFJLEVBQUUsQ0FBQztZQUN4QixpQkFBaUIsQ0FBQyxDQUFDLENBQUMsR0FBRyxDQUFDLENBQUMsQ0FBQyxFQUFFLEtBQUssQ0FBQyxFQUFFLEVBQUU7Z0JBQ2xDLElBQUksQ0FBQyxLQUFLLEVBQUU7b0JBQ1IsT0FBTztpQkFDVjtnQkFDRCxRQUFRLEtBQUssQ0FBQyxXQUFXLEVBQUUsRUFBRTtvQkFDekIsS0FBSyxLQUFLO3dCQUNOLE9BQU8sR0FBRyxDQUFDO29CQUNmLEtBQUssS0FBSzt3QkFDTixPQUFPLEdBQUcsQ0FBQztvQkFDZjt3QkFDSSxPQUFPLEdBQUcsQ0FBQztpQkFDbEI7WUFDTCxDQUFDLENBQUMsQ0FBQyxDQUFDLElBQUksQ0FBQyxLQUFLLENBQUMsZUFBZSxDQUFDLElBQUksRUFBRSxDQUFDLENBQUMsQ0FBQztZQUV4QyxDQUFDLElBQUksQ0FBQyxHQUFHLENBQUMsSUFBSSxDQUFDLFNBQVMsQ0FBQyxJQUFJLENBQUMsQ0FBQztTQUNsQztRQUVELE9BQU8sTUFBTSxDQUFDLFdBQVcsQ0FBQyxDQUFDLENBQUMsVUFBVSxDQUFDLENBQUMsR0FBRyxFQUFFLENBQUMsS0FBSyxDQUFDLENBQUMsQ0FBQyxDQUFDLEdBQUcsQ0FBQyxFQUFFLENBQUMsRUFBRTtZQUM1RCxNQUFNLFFBQVEsR0FBRyxDQUFDLENBQUMsSUFBSSxFQUFFLEVBQUUsQ0FBQyxDQUFDLEVBQUUsQ0FBQyxDQUFDLENBQUMsQ0FBQyxJQUFJLEVBQUUsQ0FBQyxJQUFJLEVBQUUsQ0FBQztZQUNqRCxNQUFNLE9BQU8sR0FBRztnQkFDWixDQUFDLEVBQUUsQ0FBQztnQkFDSixDQUFDLEVBQUUsQ0FBQztnQkFDSixDQUFDLEVBQUUsQ0FBQzthQUNQLENBQUM7WUFDRixDQUFDLENBQUMsSUFBSSxFQUFFLEVBQUUsQ0FBQyxDQUFDLElBQUksQ0FBQyxDQUFDLEtBQUssRUFBRSxFQUFFLEVBQUUsRUFBRTtnQkFDM0IsTUFBTSxVQUFVLEdBQUcsaUJBQWlCLENBQUMsS0FBSyxDQUFDLENBQUM7Z0JBQzVDLElBQUksVUFBVSxFQUFFO29CQUNaLE9BQU8sQ0FBQyxVQUFVLENBQUMsSUFBSSxDQUFDLENBQUMsQ0FBQyxFQUFFLENBQUMsQ0FBQyxJQUFJLEVBQUUsQ0FBQyxPQUFPLENBQUMsS0FBSyxFQUFFLEVBQUUsQ0FBQyxDQUFDO2lCQUMzRDtZQUNMLENBQUMsQ0FBQyxDQUFDO1lBQ0gsT0FBTyxDQUFDLFFBQVEsRUFBRSxPQUFPLENBQUMsQ0FBQztRQUMvQixDQUFDLENBQUMsQ0FBQyxDQUFDO0lBQ1IsQ0FBQztDQUNKO0FBcERELDBEQW9EQyJ9