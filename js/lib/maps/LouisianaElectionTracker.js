"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const sync_1 = __importDefault(require("csv-parse/lib/sync"));
const http_1 = require("../http");
class LouisianaElectionTracker {
    constructor(context) {
        this.context = context;
    }
    isEligible(stateName) {
        return stateName.toLocaleLowerCase() === "louisiana";
    }
    async getCountyTotals() {
        const baseUrl = `https://voterportal.sos.la.gov/ElectionResults/ElectionResults/Data?blob=${this.context}`;
        const [fields, ...results] = sync_1.default(await new http_1.HttpFetch().fetch(baseUrl));
        const parishIndex = fields.findIndex(field => field === "Parish");
        if (parishIndex < 0) {
            throw new Error(`No parish field found: ${JSON.stringify(fields)}`);
        }
        const candidatesIndices = fields.map(field => {
            const [, party] = field.match(/\((.+?)\)\s*$/) || [];
            if (!party) {
                return;
            }
            switch (party.toUpperCase()) {
                case "REP":
                    return "r";
                case "DEM":
                    return "d";
                default:
                    return "i";
            }
        });
        return Object.fromEntries(results.map(row => {
            const results = {
                r: 0,
                d: 0,
                i: 0
            };
            row.forEach((entry, index) => {
                const indexParty = candidatesIndices[index];
                if (indexParty) {
                    results[indexParty] += +entry;
                }
            });
            return [row[parishIndex], results];
        }));
    }
}
exports.LouisianaElectionTracker = LouisianaElectionTracker;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiTG91aXNpYW5hRWxlY3Rpb25UcmFja2VyLmpzIiwic291cmNlUm9vdCI6IiIsInNvdXJjZXMiOlsiTG91aXNpYW5hRWxlY3Rpb25UcmFja2VyLnRzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiI7Ozs7O0FBRUEsOERBQTBDO0FBQzFDLGtDQUFvQztBQUdwQyxNQUFhLHdCQUF3QjtJQUVqQyxZQUFvQixPQUFlO1FBQWYsWUFBTyxHQUFQLE9BQU8sQ0FBUTtJQUNuQyxDQUFDO0lBRUQsVUFBVSxDQUFDLFNBQWlCO1FBQ3hCLE9BQU8sU0FBUyxDQUFDLGlCQUFpQixFQUFFLEtBQUssV0FBVyxDQUFDO0lBQ3pELENBQUM7SUFFRCxLQUFLLENBQUMsZUFBZTtRQUNqQixNQUFNLE9BQU8sR0FBRyw0RUFBNEUsSUFBSSxDQUFDLE9BQU8sRUFBRSxDQUFDO1FBRTNHLE1BQU0sQ0FBQyxNQUFNLEVBQUUsR0FBRyxPQUFPLENBQUMsR0FBZ0IsY0FBUSxDQUFDLE1BQU0sSUFBSSxnQkFBUyxFQUFFLENBQUMsS0FBSyxDQUFDLE9BQU8sQ0FBQyxDQUFFLENBQUM7UUFFMUYsTUFBTSxXQUFXLEdBQUcsTUFBTSxDQUFDLFNBQVMsQ0FBQyxLQUFLLENBQUMsRUFBRSxDQUFDLEtBQUssS0FBSyxRQUFRLENBQUMsQ0FBQztRQUNsRSxJQUFJLFdBQVcsR0FBRyxDQUFDLEVBQUU7WUFDakIsTUFBTSxJQUFJLEtBQUssQ0FBQywwQkFBMEIsSUFBSSxDQUFDLFNBQVMsQ0FBQyxNQUFNLENBQUMsRUFBRSxDQUFDLENBQUE7U0FDdEU7UUFDRCxNQUFNLGlCQUFpQixHQUFHLE1BQU0sQ0FBQyxHQUFHLENBQUMsS0FBSyxDQUFDLEVBQUU7WUFDekMsTUFBTSxDQUFDLEVBQUUsS0FBSyxDQUFDLEdBQUcsS0FBSyxDQUFDLEtBQUssQ0FBQyxlQUFlLENBQUMsSUFBSSxFQUFFLENBQUM7WUFDckQsSUFBSSxDQUFDLEtBQUssRUFBRTtnQkFDUixPQUFPO2FBQ1Y7WUFDRCxRQUFRLEtBQUssQ0FBQyxXQUFXLEVBQUUsRUFBRTtnQkFDekIsS0FBSyxLQUFLO29CQUNOLE9BQU8sR0FBRyxDQUFDO2dCQUNmLEtBQUssS0FBSztvQkFDTixPQUFPLEdBQUcsQ0FBQztnQkFDZjtvQkFDSSxPQUFPLEdBQUcsQ0FBQzthQUNsQjtRQUNMLENBQUMsQ0FBQyxDQUFDO1FBRUgsT0FBTyxNQUFNLENBQUMsV0FBVyxDQUFDLE9BQU8sQ0FBQyxHQUFHLENBQUMsR0FBRyxDQUFDLEVBQUU7WUFDeEMsTUFBTSxPQUFPLEdBQUc7Z0JBQ1osQ0FBQyxFQUFFLENBQUM7Z0JBQ0osQ0FBQyxFQUFFLENBQUM7Z0JBQ0osQ0FBQyxFQUFFLENBQUM7YUFDUCxDQUFDO1lBRUYsR0FBRyxDQUFDLE9BQU8sQ0FBQyxDQUFDLEtBQUssRUFBRSxLQUFLLEVBQUUsRUFBRTtnQkFDekIsTUFBTSxVQUFVLEdBQUcsaUJBQWlCLENBQUMsS0FBSyxDQUFDLENBQUM7Z0JBQzVDLElBQUksVUFBVSxFQUFFO29CQUNaLE9BQU8sQ0FBQyxVQUFVLENBQUMsSUFBSSxDQUFDLEtBQUssQ0FBQztpQkFDakM7WUFDTCxDQUFDLENBQUMsQ0FBQztZQUNILE9BQU8sQ0FBQyxHQUFHLENBQUMsV0FBVyxDQUFDLEVBQUUsT0FBTyxDQUFDLENBQUM7UUFDdkMsQ0FBQyxDQUFDLENBQUMsQ0FBQztJQUNSLENBQUM7Q0FFSjtBQWxERCw0REFrREMifQ==