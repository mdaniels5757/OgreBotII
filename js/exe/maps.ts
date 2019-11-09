import { PoliticoElectionTracker } from './../lib/maps/PoliticoElectionTracker';
import { CharityElectionsTracker } from './../lib/maps/CharityElectionsTracker';

import {StatesBuilder} from "../lib/maps/Maps";
import { standardElectionResultColorer } from '../lib/maps/ElectionResultsColorer';
import Io from '../lib/io';
(async () => {
    const statesBuilder = new StatesBuilder().addElectionTrackers(
 //       new CharityElectionsTracker("Kentucky", "https://results.enr.clarityelections.com//KY/97213/234751/json/ALL.json"), 
        new CharityElectionsTracker("Kentucky", "https://results.enr.clarityelections.com//KY/97213/234751/json/ALL.json", 1), //SOS
        new CharityElectionsTracker("Georgia", "https://results.enr.clarityelections.com/GA/93711/224803/json/ALL.json"), //SOS
        new PoliticoElectionTracker("20191105-general-election/mississippi/"))
        .setElectionResultHandler(standardElectionResultColorer);
    await statesBuilder.build(`${Io.PROJECT_DIR}/../Desktop`);
    console.log("Done");
})();