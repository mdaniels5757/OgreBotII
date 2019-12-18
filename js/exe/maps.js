"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const Maps_1 = require("../lib/maps/Maps");
const ElectionResultsColorer_1 = require("../lib/maps/ElectionResultsColorer");
const io_1 = __importDefault(require("../lib/io"));
const IllinoisElectionTracker_1 = require("../lib/maps/IllinoisElectionTracker");
(async () => {
    const statesBuilder = new Maps_1.StatesBuilder().addElectionTrackers(
    //       new CharityElectionsTracker("Kentucky", "https://results.enr.clarityelections.com//KY/97213/234751/json/ALL.json"), 
    //new CharityElectionsTracker("Kentucky", "https://results.enr.clarityelections.com//KY/97213/234751/json/ALL.json", 1), //SOS
    // new CharityElectionsTracker("Kentucky", "https://results.enr.clarityelections.com//KY/97213/234751/json/ALL.json", 2), //AG
    //new CharityElectionsTracker("Georgia", "https://results.enr.clarityelections.com/GA/93711/224803/json/ALL.json"), //SOS
    //new CharityElectionsTracker("Georgia", "https://results.enr.clarityelections.com/GA/91639/222278/json/ALL.json", 1),
    //new VirginiaElectionTracker("87708") //2017 Gov
    //new ClarionElectionsTracker(`${Io.PROJECT_DIR}/artifacts/filestuff.txt`, "Mississippi")
    //new LouisianaElectionTracker("20191116/csv/ByParish_57627.csv")
    new IllinoisElectionTracker_1.IllinoisElectionTracker(2016))
        .setElectionResultHandler(ElectionResultsColorer_1.standardElectionResultColorer);
    await statesBuilder.build(`${io_1.default.PROJECT_DIR}/../Desktop`);
    console.log("Done");
})();
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoibWFwcy5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbIm1hcHMudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7Ozs7QUFHQSwyQ0FBK0M7QUFDL0MsK0VBQW1GO0FBQ25GLG1EQUEyQjtBQUczQixpRkFBOEU7QUFFOUUsQ0FBQyxLQUFLLElBQUksRUFBRTtJQUNSLE1BQU0sYUFBYSxHQUFHLElBQUksb0JBQWEsRUFBRSxDQUFDLG1CQUFtQjtJQUNoRSw2SEFBNkg7SUFDdEgsOEhBQThIO0lBQy9ILDhIQUE4SDtJQUM3SCx5SEFBeUg7SUFDekgsc0hBQXNIO0lBQ3RILGlEQUFpRDtJQUNqRCx5RkFBeUY7SUFDekYsaUVBQWlFO0lBQ2pFLElBQUksaURBQXVCLENBQUMsSUFBSSxDQUFDLENBQ2hDO1NBQ0Esd0JBQXdCLENBQUMsc0RBQTZCLENBQUMsQ0FBQztJQUM3RCxNQUFNLGFBQWEsQ0FBQyxLQUFLLENBQUMsR0FBRyxZQUFFLENBQUMsV0FBVyxhQUFhLENBQUMsQ0FBQztJQUMxRCxPQUFPLENBQUMsR0FBRyxDQUFDLE1BQU0sQ0FBQyxDQUFDO0FBQ3hCLENBQUMsQ0FBQyxFQUFFLENBQUMifQ==