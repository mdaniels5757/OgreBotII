import fs from "fs";
import {matchAll} from "./utils";
export default class Io {

    private static properties: Map<string, Map<string, string>> = new Map();

    public static get projectDir() {
        return `${__dirname}/../..`;
    }

    public static getProperty(file: string, property: string) {
        let thisProperties = this.properties.get(file);
        if (!thisProperties) {
            thisProperties = new Map();
            const contents = fs.readFileSync(`${this.projectDir}/properties/${file}.properties`, {encoding: "UTF-8"});
            for (const [, key, val] of matchAll(/^\s*(.+?)\s*\=\s*"?(.+)"\s*?$/gm, contents)) {
                thisProperties.set(key, val);
            }
            this.properties.set(file, thisProperties);            
        }

        return thisProperties.get(property);
    }

    public static readFile(filename: string) {
        return new Promise<string>((resolve, reject) => {
            fs.readFile(filename, function(err, data) {
                if (err) {
                    reject(err);
                } else {
                    resolve(data.toString());
                }
            })
        });
    }

}