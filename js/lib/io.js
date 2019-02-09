"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const fs_1 = __importDefault(require("fs"));
const utils_1 = require("./utils");
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
Io.properties = new Map();
exports.default = Io;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiaW8uanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJpby50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7OztBQUFBLDRDQUFvQjtBQUNwQixtQ0FBaUM7QUFDakMsTUFBcUIsRUFBRTtJQUlaLE1BQU0sS0FBSyxVQUFVO1FBQ3hCLE9BQU8sR0FBRyxTQUFTLFFBQVEsQ0FBQztJQUNoQyxDQUFDO0lBRU0sTUFBTSxDQUFDLFdBQVcsQ0FBQyxJQUFZLEVBQUUsUUFBZ0I7UUFDcEQsSUFBSSxjQUFjLEdBQUcsSUFBSSxDQUFDLFVBQVUsQ0FBQyxHQUFHLENBQUMsSUFBSSxDQUFDLENBQUM7UUFDL0MsSUFBSSxDQUFDLGNBQWMsRUFBRTtZQUNqQixjQUFjLEdBQUcsSUFBSSxHQUFHLEVBQUUsQ0FBQztZQUMzQixNQUFNLFFBQVEsR0FBRyxZQUFFLENBQUMsWUFBWSxDQUFDLEdBQUcsSUFBSSxDQUFDLFVBQVUsZUFBZSxJQUFJLGFBQWEsRUFBRSxFQUFDLFFBQVEsRUFBRSxPQUFPLEVBQUMsQ0FBQyxDQUFDO1lBQzFHLEtBQUssTUFBTSxDQUFDLEVBQUUsR0FBRyxFQUFFLEdBQUcsQ0FBQyxJQUFJLGdCQUFRLENBQUMsaUNBQWlDLEVBQUUsUUFBUSxDQUFDLEVBQUU7Z0JBQzlFLGNBQWMsQ0FBQyxHQUFHLENBQUMsR0FBRyxFQUFFLEdBQUcsQ0FBQyxDQUFDO2FBQ2hDO1lBQ0QsSUFBSSxDQUFDLFVBQVUsQ0FBQyxHQUFHLENBQUMsSUFBSSxFQUFFLGNBQWMsQ0FBQyxDQUFDO1NBQzdDO1FBRUQsT0FBTyxjQUFjLENBQUMsR0FBRyxDQUFDLFFBQVEsQ0FBQyxDQUFDO0lBQ3hDLENBQUM7SUFFTSxNQUFNLENBQUMsUUFBUSxDQUFDLFFBQWdCO1FBQ25DLE9BQU8sSUFBSSxPQUFPLENBQVMsQ0FBQyxPQUFPLEVBQUUsTUFBTSxFQUFFLEVBQUU7WUFDM0MsWUFBRSxDQUFDLFFBQVEsQ0FBQyxRQUFRLEVBQUUsVUFBUyxHQUFHLEVBQUUsSUFBSTtnQkFDcEMsSUFBSSxHQUFHLEVBQUU7b0JBQ0wsTUFBTSxDQUFDLEdBQUcsQ0FBQyxDQUFDO2lCQUNmO3FCQUFNO29CQUNILE9BQU8sQ0FBQyxJQUFJLENBQUMsUUFBUSxFQUFFLENBQUMsQ0FBQztpQkFDNUI7WUFDTCxDQUFDLENBQUMsQ0FBQTtRQUNOLENBQUMsQ0FBQyxDQUFDO0lBQ1AsQ0FBQzs7QUE5QmMsYUFBVSxHQUFxQyxJQUFJLEdBQUcsRUFBRSxDQUFDO0FBRjVFLHFCQWtDQyJ9