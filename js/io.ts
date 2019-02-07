
import fs from "fs";
import {matchAll} from "./utils";
enum MediawikiApi {
    COMMONS = "https://commons.wikimedia.org/w/api.php"
}

enum MediawikiUsername {
    OGREBOT_2 = "OgreBot_2"
}

interface PropertiesFile {
    username: MediawikiUsername;
    api: MediawikiApi
}

export default class Io {


    private static properties: Map<string, Map<string, string>> = new Map();

    public static getProperty(file: string, property: string) {
        let thisProperties = this.properties.get(file);
        if (!thisProperties) {
            thisProperties = new Map();
            const contents = fs.readFileSync(`../properties/${file}.properties`, {encoding: "UTF-8"});
            for (const [, key, val] of matchAll(() => new RegExp(/^\s*(.+?)\s*\=\s*"?(.+)"\s*?$/gm), contents)) {
                thisProperties.set(key, val);
            }
            this.properties.set(file, thisProperties);            
        }

        return thisProperties.get(property);
    }

}