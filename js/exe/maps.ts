import { CharityElectionsTracker } from './../lib/maps/CharityElectionsTracker';

import {StatesBuilder} from "../lib/maps/Maps";
import { standardElectionResultColorer } from '../lib/maps/ElectionResultsColorer';
import Io from '../lib/io';
(async () => {
    const statesBuilder = new StatesBuilder().addElectionTrackers(
        new CharityElectionsTracker("Kentucky", "https://results.enr.clarityelections.com//KY/97213/234751/json/ALL.json"))
        .setElectionResultHandler(standardElectionResultColorer);
    await statesBuilder.build(`${Io.PROJECT_DIR}/../Desktop`);
    console.log("Done");
})();