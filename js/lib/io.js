"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const fs_1 = __importDefault(require("fs"));
const utils_1 = require("./utils");
const os_1 = require("os");
class Io {
    static get projectDir() {
        return `${__dirname}/../..`;
    }
    static getProperty(file, property) {
        let thisProperties = this.properties.get(file);
        if (!thisProperties) {
            thisProperties = new Map();
            const contents = fs_1.default.readFileSync(`${this.projectDir}/properties/${file}.properties`, { encoding: "UTF-8" });
            for (const [, key, val] of utils_1.matchAll(/^\s*(.+?)\s*\=\s*"?(.+)"\s*?$/gm, contents)) {
                thisProperties.set(key, val);
            }
            this.properties.set(file, thisProperties);
        }
        return thisProperties.get(property);
    }
    static writeFile(filename, data, options = {}) {
        return new Promise((resolve, reject) => {
            fs_1.default.writeFile(filename, data, options, function (err) {
                if (err) {
                    reject(err);
                }
                else {
                    resolve(data.toString());
                }
            });
        });
    }
    static readFile(filename) {
        return new Promise((resolve, reject) => {
            fs_1.default.readFile(filename, function (err, data) {
                if (err) {
                    reject(err);
                }
                else {
                    resolve(data.toString());
                }
            });
        });
    }
}
Io.EOL = os_1.EOL;
Io.properties = new Map();
exports.default = Io;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiaW8uanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJpby50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7OztBQUFBLDRDQUFvQjtBQUNwQixtQ0FBaUM7QUFDakMsMkJBQXVCO0FBQ3ZCLE1BQXFCLEVBQUU7SUFNWixNQUFNLEtBQUssVUFBVTtRQUN4QixPQUFPLEdBQUcsU0FBUyxRQUFRLENBQUM7SUFDaEMsQ0FBQztJQUVNLE1BQU0sQ0FBQyxXQUFXLENBQUMsSUFBWSxFQUFFLFFBQWdCO1FBQ3BELElBQUksY0FBYyxHQUFHLElBQUksQ0FBQyxVQUFVLENBQUMsR0FBRyxDQUFDLElBQUksQ0FBQyxDQUFDO1FBQy9DLElBQUksQ0FBQyxjQUFjLEVBQUU7WUFDakIsY0FBYyxHQUFHLElBQUksR0FBRyxFQUFFLENBQUM7WUFDM0IsTUFBTSxRQUFRLEdBQUcsWUFBRSxDQUFDLFlBQVksQ0FBQyxHQUFHLElBQUksQ0FBQyxVQUFVLGVBQWUsSUFBSSxhQUFhLEVBQUUsRUFBQyxRQUFRLEVBQUUsT0FBTyxFQUFDLENBQUMsQ0FBQztZQUMxRyxLQUFLLE1BQU0sQ0FBQyxFQUFFLEdBQUcsRUFBRSxHQUFHLENBQUMsSUFBSSxnQkFBUSxDQUFDLGlDQUFpQyxFQUFFLFFBQVEsQ0FBQyxFQUFFO2dCQUM5RSxjQUFjLENBQUMsR0FBRyxDQUFDLEdBQUcsRUFBRSxHQUFHLENBQUMsQ0FBQzthQUNoQztZQUNELElBQUksQ0FBQyxVQUFVLENBQUMsR0FBRyxDQUFDLElBQUksRUFBRSxjQUFjLENBQUMsQ0FBQztTQUM3QztRQUVELE9BQU8sY0FBYyxDQUFDLEdBQUcsQ0FBQyxRQUFRLENBQUMsQ0FBQztJQUN4QyxDQUFDO0lBRU0sTUFBTSxDQUFDLFNBQVMsQ0FBQyxRQUFnQixFQUFFLElBQVMsRUFBRSxVQUErQixFQUFFO1FBQ2xGLE9BQU8sSUFBSSxPQUFPLENBQVMsQ0FBQyxPQUFPLEVBQUUsTUFBTSxFQUFFLEVBQUU7WUFDM0MsWUFBRSxDQUFDLFNBQVMsQ0FBQyxRQUFRLEVBQUUsSUFBSSxFQUFFLE9BQU8sRUFBRSxVQUFTLEdBQWlDO2dCQUM1RSxJQUFJLEdBQUcsRUFBRTtvQkFDTCxNQUFNLENBQUMsR0FBRyxDQUFDLENBQUM7aUJBQ2Y7cUJBQU07b0JBQ0gsT0FBTyxDQUFDLElBQUksQ0FBQyxRQUFRLEVBQUUsQ0FBQyxDQUFDO2lCQUM1QjtZQUNMLENBQUMsQ0FBQyxDQUFDO1FBQ1AsQ0FBQyxDQUFDLENBQUM7SUFDUCxDQUFDO0lBRU0sTUFBTSxDQUFDLFFBQVEsQ0FBQyxRQUFnQjtRQUNuQyxPQUFPLElBQUksT0FBTyxDQUFTLENBQUMsT0FBTyxFQUFFLE1BQU0sRUFBRSxFQUFFO1lBQzNDLFlBQUUsQ0FBQyxRQUFRLENBQUMsUUFBUSxFQUFFLFVBQVMsR0FBRyxFQUFFLElBQUk7Z0JBQ3BDLElBQUksR0FBRyxFQUFFO29CQUNMLE1BQU0sQ0FBQyxHQUFHLENBQUMsQ0FBQztpQkFDZjtxQkFBTTtvQkFDSCxPQUFPLENBQUMsSUFBSSxDQUFDLFFBQVEsRUFBRSxDQUFDLENBQUM7aUJBQzVCO1lBQ0wsQ0FBQyxDQUFDLENBQUE7UUFDTixDQUFDLENBQUMsQ0FBQztJQUNQLENBQUM7O0FBNUNzQixNQUFHLEdBQUcsUUFBRyxDQUFDO0FBRWxCLGFBQVUsR0FBcUMsSUFBSSxHQUFHLEVBQUUsQ0FBQztBQUo1RSxxQkFnREMifQ==