import fs from "fs";
import io from "../lib/io";
import {matchAll, sortCaseInsensitive} from "../lib/stringUtils";

const LOG_DIR = `${io.PROJECT_DIR}/log/category-files`;
const galleryNames = new Set<String>();
(async function() {
    var files = fs.readdirSync(LOG_DIR);
    await Promise.all(fs.readdirSync(LOG_DIR).map(async file => {
        for (const [, match] of matchAll(/^(.+?)\|/gm, await io.readFile(`${LOG_DIR}/${file}`))) {
            galleryNames.add(match);
        }
    }));
    console.log(JSON.stringify({
        dates: files.map(file => file.replace(/^(\d{4})(\d{2})(\d{2})\.log$/, "$1-$2-$3")).sort(),
        galleries: Array.from(galleryNames).sort(sortCaseInsensitive)
    }));
}());
