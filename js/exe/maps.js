"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const VirginiaElectionTracker_1 = require("./../lib/maps/VirginiaElectionTracker");
const Maps_1 = require("../lib/maps/Maps");
const ElectionResultsColorer_1 = require("../lib/maps/ElectionResultsColorer");
const io_1 = __importDefault(require("../lib/io"));
(async () => {
    const statesBuilder = new Maps_1.StatesBuilder().addElectionTrackers(
    //       new CharityElectionsTracker("Kentucky", "https://results.enr.clarityelections.com//KY/97213/234751/json/ALL.json"), 
    //new CharityElectionsTracker("Kentucky", "https://results.enr.clarityelections.com//KY/97213/234751/json/ALL.json", 1), //SOS
    // new CharityElectionsTracker("Kentucky", "https://results.enr.clarityelections.com//KY/97213/234751/json/ALL.json", 2), //AG
    //new CharityElectionsTracker("Georgia", "https://results.enr.clarityelections.com/GA/93711/224803/json/ALL.json"), //SOS
    //new CharityElectionsTracker("Georgia", "https://results.enr.clarityelections.com/GA/91639/222278/json/ALL.json", 1),
    new VirginiaElectionTracker_1.VirginiaElectionTracker("87708") //2017 Gov
    //new PoliticoElectionTracker("20191105-general-election/mississippi/")
    )
        .setElectionResultHandler(ElectionResultsColorer_1.standardElectionResultColorer);
    await statesBuilder.build(`${io_1.default.PROJECT_DIR}/../Desktop`);
    console.log("Done");
})();
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoibWFwcy5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbIm1hcHMudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7Ozs7QUFBQSxtRkFBZ0Y7QUFJaEYsMkNBQStDO0FBQy9DLCtFQUFtRjtBQUNuRixtREFBMkI7QUFDM0IsQ0FBQyxLQUFLLElBQUksRUFBRTtJQUNSLE1BQU0sYUFBYSxHQUFHLElBQUksb0JBQWEsRUFBRSxDQUFDLG1CQUFtQjtJQUNoRSw2SEFBNkg7SUFDdEgsOEhBQThIO0lBQy9ILDhIQUE4SDtJQUM3SCx5SEFBeUg7SUFDekgsc0hBQXNIO0lBQ3RILElBQUksaURBQXVCLENBQUMsT0FBTyxDQUFDLENBQUMsVUFBVTtJQUMvQyx1RUFBdUU7S0FDdEU7U0FDQSx3QkFBd0IsQ0FBQyxzREFBNkIsQ0FBQyxDQUFDO0lBQzdELE1BQU0sYUFBYSxDQUFDLEtBQUssQ0FBQyxHQUFHLFlBQUUsQ0FBQyxXQUFXLGFBQWEsQ0FBQyxDQUFDO0lBQzFELE9BQU8sQ0FBQyxHQUFHLENBQUMsTUFBTSxDQUFDLENBQUM7QUFDeEIsQ0FBQyxDQUFDLEVBQUUsQ0FBQyJ9