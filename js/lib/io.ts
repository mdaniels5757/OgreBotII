import fs from "fs";
import {matchAll} from "./utils";
import {EOL} from "os";
export default class Io {

    public static readonly EOL = EOL;

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

    public static writeFile(filename: string, data: any, options: fs.WriteFileOptions = {}) {
        return new Promise<string>((resolve, reject) => {
            fs.writeFile(filename, data, options, function(err: NodeJS.ErrnoException | null) {
                if (err) {
                    reject(err);
                } else {
                    resolve(data.toString());
                }
            });
        });
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