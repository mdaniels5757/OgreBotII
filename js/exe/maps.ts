import { VirginiaElectionTracker } from './../lib/maps/VirginiaElectionTracker';
import { PoliticoElectionTracker } from './../lib/maps/PoliticoElectionTracker';
import { CharityElectionsTracker } from './../lib/maps/CharityElectionsTracker';
import {StatesBuilder} from "../lib/maps/Maps";
import { standardElectionResultColorer } from '../lib/maps/ElectionResultsColorer';
import Io from '../lib/io';
import { ClarionElectionsTracker } from '../lib/maps/ClarionElectionsTracker';
import { LouisianaElectionTracker } from '../lib/maps/LouisianaElectionTracker';

(async () => {
    const statesBuilder = new StatesBuilder().addElectionTrackers(
 //       new CharityElectionsTracker("Kentucky", "https://results.enr.clarityelections.com//KY/97213/234751/json/ALL.json"), 
        //new CharityElectionsTracker("Kentucky", "https://results.enr.clarityelections.com//KY/97213/234751/json/ALL.json", 1), //SOS
       // new CharityElectionsTracker("Kentucky", "https://results.enr.clarityelections.com//KY/97213/234751/json/ALL.json", 2), //AG
        //new CharityElectionsTracker("Georgia", "https://results.enr.clarityelections.com/GA/93711/224803/json/ALL.json"), //SOS
        //new CharityElectionsTracker("Georgia", "https://results.enr.clarityelections.com/GA/91639/222278/json/ALL.json", 1),
        //new VirginiaElectionTracker("87708") //2017 Gov
        //new ClarionElectionsTracker(`${Io.PROJECT_DIR}/artifacts/filestuff.txt`, "Mississippi")
        new LouisianaElectionTracker("20191116/csv/ByParish_57627.csv")
        )
        .setElectionResultHandler(standardElectionResultColorer);
    await statesBuilder.build(`${Io.PROJECT_DIR}/../Desktop`);
    console.log("Done");
})();