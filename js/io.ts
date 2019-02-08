import fs from "fs";
import {matchAll} from "./utils";
export default class Io {


    private static properties: Map<string, Map<string, string>> = new Map();

    public static getProperty(file: string, property: string) {
        let thisProperties = this.properties.get(file);
        if (!thisProperties) {
            thisProperties = new Map();
            const contents = fs.readFileSync(`${__dirname}/../properties/${file}.properties`, {encoding: "UTF-8"});
            for (const [, key, val] of matchAll(() => new RegExp(/^\s*(.+?)\s*\=\s*"?(.+)"\s*?$/gm), contents)) {
                thisProperties.set(key, val);
            }
            this.properties.set(file, thisProperties);            
        }

        return thisProperties.get(property);
    }

}