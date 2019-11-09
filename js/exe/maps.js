"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const PoliticoElectionTracker_1 = require("./../lib/maps/PoliticoElectionTracker");
const CharityElectionsTracker_1 = require("./../lib/maps/CharityElectionsTracker");
const Maps_1 = require("../lib/maps/Maps");
const ElectionResultsColorer_1 = require("../lib/maps/ElectionResultsColorer");
const io_1 = __importDefault(require("../lib/io"));
(async () => {
    const statesBuilder = new Maps_1.StatesBuilder().addElectionTrackers(
    //       new CharityElectionsTracker("Kentucky", "https://results.enr.clarityelections.com//KY/97213/234751/json/ALL.json"), 
    new CharityElectionsTracker_1.CharityElectionsTracker("Kentucky", "https://results.enr.clarityelections.com//KY/97213/234751/json/ALL.json", 1), //SOS
    new PoliticoElectionTracker_1.PoliticoElectionTracker("20191105-general-election/mississippi/"))
        .setElectionResultHandler(ElectionResultsColorer_1.standardElectionResultColorer);
    await statesBuilder.build(`${io_1.default.PROJECT_DIR}/../Desktop`);
    console.log("Done");
})();
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoibWFwcy5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbIm1hcHMudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7Ozs7QUFBQSxtRkFBZ0Y7QUFDaEYsbUZBQWdGO0FBRWhGLDJDQUErQztBQUMvQywrRUFBbUY7QUFDbkYsbURBQTJCO0FBQzNCLENBQUMsS0FBSyxJQUFJLEVBQUU7SUFDUixNQUFNLGFBQWEsR0FBRyxJQUFJLG9CQUFhLEVBQUUsQ0FBQyxtQkFBbUI7SUFDaEUsNkhBQTZIO0lBQ3RILElBQUksaURBQXVCLENBQUMsVUFBVSxFQUFFLHlFQUF5RSxFQUFFLENBQUMsQ0FBQyxFQUFFLEtBQUs7SUFDNUgsSUFBSSxpREFBdUIsQ0FBQyx3Q0FBd0MsQ0FBQyxDQUFDO1NBQ3JFLHdCQUF3QixDQUFDLHNEQUE2QixDQUFDLENBQUM7SUFDN0QsTUFBTSxhQUFhLENBQUMsS0FBSyxDQUFDLEdBQUcsWUFBRSxDQUFDLFdBQVcsYUFBYSxDQUFDLENBQUM7SUFDMUQsT0FBTyxDQUFDLEdBQUcsQ0FBQyxNQUFNLENBQUMsQ0FBQztBQUN4QixDQUFDLENBQUMsRUFBRSxDQUFDIn0=