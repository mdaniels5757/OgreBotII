"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const fs_1 = __importDefault(require("fs"));
const stringUtils_1 = require("./stringUtils");
const os_1 = require("os");
const cachable_1 = require("./decorators/cachable");
class Io {
    static getProperties(file) {
        const thisProperties = new Map();
        const contents = fs_1.default.readFileSync(`${this.PROJECT_DIR}/properties/${file}.properties`, { encoding: "UTF-8" });
        for (const [, key, val] of stringUtils_1.matchAll(/^\s*(.+?)\s*\=\s*"?(.+)"\s*?$/gm, contents)) {
            thisProperties.set(key, val);
        }
        return thisProperties;
    }
    static getProperty(file, property) {
        return this.getProperties(file).get(property);
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
Io.readDir = fs_1.default.readdirSync.bind(fs_1.default);
Io.EOL = os_1.EOL;
Io.PROJECT_DIR = `${__dirname}/../..`;
__decorate([
    cachable_1.cachable()
], Io, "getProperties", null);
exports.default = Io;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiaW8uanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJpby50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7OztBQUFBLDRDQUFvQjtBQUNwQiwrQ0FBdUM7QUFDdkMsMkJBQXVCO0FBQ3ZCLG9EQUFpRDtBQUNqRCxNQUFxQixFQUFFO0lBU1gsTUFBTSxDQUFDLGFBQWEsQ0FBQyxJQUFZO1FBQ3JDLE1BQU0sY0FBYyxHQUFHLElBQUksR0FBRyxFQUFFLENBQUM7UUFDakMsTUFBTSxRQUFRLEdBQUcsWUFBRSxDQUFDLFlBQVksQ0FBQyxHQUFHLElBQUksQ0FBQyxXQUFXLGVBQWUsSUFBSSxhQUFhLEVBQUUsRUFBQyxRQUFRLEVBQUUsT0FBTyxFQUFDLENBQUMsQ0FBQztRQUMzRyxLQUFLLE1BQU0sQ0FBQyxFQUFFLEdBQUcsRUFBRSxHQUFHLENBQUMsSUFBSSxzQkFBUSxDQUFDLGlDQUFpQyxFQUFFLFFBQVEsQ0FBQyxFQUFFO1lBQzlFLGNBQWMsQ0FBQyxHQUFHLENBQUMsR0FBRyxFQUFFLEdBQUcsQ0FBQyxDQUFDO1NBQ2hDO1FBQ0QsT0FBTyxjQUFjLENBQUM7SUFFMUIsQ0FBQztJQUVNLE1BQU0sQ0FBQyxXQUFXLENBQUMsSUFBWSxFQUFFLFFBQWdCO1FBQ3BELE9BQU8sSUFBSSxDQUFDLGFBQWEsQ0FBQyxJQUFJLENBQUMsQ0FBQyxHQUFHLENBQUMsUUFBUSxDQUFDLENBQUM7SUFDbEQsQ0FBQztJQUVNLE1BQU0sQ0FBQyxTQUFTLENBQUMsUUFBZ0IsRUFBRSxJQUFTLEVBQUUsVUFBK0IsRUFBRTtRQUNsRixPQUFPLElBQUksT0FBTyxDQUFTLENBQUMsT0FBTyxFQUFFLE1BQU0sRUFBRSxFQUFFO1lBQzNDLFlBQUUsQ0FBQyxTQUFTLENBQUMsUUFBUSxFQUFFLElBQUksRUFBRSxPQUFPLEVBQUUsVUFBUyxHQUFpQztnQkFDNUUsSUFBSSxHQUFHLEVBQUU7b0JBQ0wsTUFBTSxDQUFDLEdBQUcsQ0FBQyxDQUFDO2lCQUNmO3FCQUFNO29CQUNILE9BQU8sQ0FBQyxJQUFJLENBQUMsUUFBUSxFQUFFLENBQUMsQ0FBQztpQkFDNUI7WUFDTCxDQUFDLENBQUMsQ0FBQztRQUNQLENBQUMsQ0FBQyxDQUFDO0lBQ1AsQ0FBQztJQUVNLE1BQU0sQ0FBQyxRQUFRLENBQUMsUUFBZ0I7UUFDbkMsT0FBTyxJQUFJLE9BQU8sQ0FBUyxDQUFDLE9BQU8sRUFBRSxNQUFNLEVBQUUsRUFBRTtZQUMzQyxZQUFFLENBQUMsUUFBUSxDQUFDLFFBQVEsRUFBRSxVQUFTLEdBQUcsRUFBRSxJQUFJO2dCQUNwQyxJQUFJLEdBQUcsRUFBRTtvQkFDTCxNQUFNLENBQUMsR0FBRyxDQUFDLENBQUM7aUJBQ2Y7cUJBQU07b0JBQ0gsT0FBTyxDQUFDLElBQUksQ0FBQyxRQUFRLEVBQUUsQ0FBQyxDQUFDO2lCQUM1QjtZQUNMLENBQUMsQ0FBQyxDQUFBO1FBQ04sQ0FBQyxDQUFDLENBQUM7SUFDUCxDQUFDOztBQTNDc0IsVUFBTyxHQUFHLFlBQUUsQ0FBQyxXQUFXLENBQUMsSUFBSSxDQUFDLFlBQUUsQ0FBQyxDQUFDO0FBRWxDLE1BQUcsR0FBRyxRQUFHLENBQUM7QUFFVixjQUFXLEdBQUksR0FBRyxTQUFTLFFBQVEsQ0FBQztBQUczRDtJQURDLG1CQUFRLEVBQUU7NkJBU1Y7QUFqQkwscUJBOENDIn0=