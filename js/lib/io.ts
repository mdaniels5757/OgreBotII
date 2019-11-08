import fs from "fs";
import {matchAll} from "./stringUtils";
import {EOL} from "os";
import { cachable } from "./decorators/cachable";
export default class Io {

    public static readonly readDir = fs.readdirSync.bind(fs);

    public static readonly EOL = EOL;

    public static readonly PROJECT_DIR  = `${__dirname}/../..`;

    @cachable()
    private static getProperties(file: string): Map<string, string> {
        const thisProperties = new Map();
        const contents = fs.readFileSync(`${this.PROJECT_DIR}/properties/${file}.properties`, {encoding: "UTF-8"});
        for (const [, key, val] of matchAll(/^\s*(.+?)\s*\=\s*"?(.+)"\s*?$/gm, contents)) {
            thisProperties.set(key, val);
        }
        return thisProperties;

    }

    public static getProperty(file: string, property: string) {
        return this.getProperties(file).get(property);
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