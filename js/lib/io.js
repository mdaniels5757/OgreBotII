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
    static get projectDir() {
        return `${__dirname}/../..`;
    }
    static getProperties(file) {
        const thisProperties = new Map();
        const contents = fs_1.default.readFileSync(`${this.projectDir}/properties/${file}.properties`, { encoding: "UTF-8" });
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
Io.EOL = os_1.EOL;
__decorate([
    cachable_1.cachable()
], Io, "getProperties", null);
exports.default = Io;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiaW8uanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJpby50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7OztBQUFBLDRDQUFvQjtBQUNwQiwrQ0FBdUM7QUFDdkMsMkJBQXVCO0FBQ3ZCLG9EQUFpRDtBQUNqRCxNQUFxQixFQUFFO0lBSVosTUFBTSxLQUFLLFVBQVU7UUFDeEIsT0FBTyxHQUFHLFNBQVMsUUFBUSxDQUFDO0lBQ2hDLENBQUM7SUFHTyxNQUFNLENBQUMsYUFBYSxDQUFDLElBQVk7UUFDckMsTUFBTSxjQUFjLEdBQUcsSUFBSSxHQUFHLEVBQUUsQ0FBQztRQUNqQyxNQUFNLFFBQVEsR0FBRyxZQUFFLENBQUMsWUFBWSxDQUFDLEdBQUcsSUFBSSxDQUFDLFVBQVUsZUFBZSxJQUFJLGFBQWEsRUFBRSxFQUFDLFFBQVEsRUFBRSxPQUFPLEVBQUMsQ0FBQyxDQUFDO1FBQzFHLEtBQUssTUFBTSxDQUFDLEVBQUUsR0FBRyxFQUFFLEdBQUcsQ0FBQyxJQUFJLHNCQUFRLENBQUMsaUNBQWlDLEVBQUUsUUFBUSxDQUFDLEVBQUU7WUFDOUUsY0FBYyxDQUFDLEdBQUcsQ0FBQyxHQUFHLEVBQUUsR0FBRyxDQUFDLENBQUM7U0FDaEM7UUFDRCxPQUFPLGNBQWMsQ0FBQztJQUUxQixDQUFDO0lBRU0sTUFBTSxDQUFDLFdBQVcsQ0FBQyxJQUFZLEVBQUUsUUFBZ0I7UUFDcEQsT0FBTyxJQUFJLENBQUMsYUFBYSxDQUFDLElBQUksQ0FBQyxDQUFDLEdBQUcsQ0FBQyxRQUFRLENBQUMsQ0FBQztJQUNsRCxDQUFDO0lBRU0sTUFBTSxDQUFDLFNBQVMsQ0FBQyxRQUFnQixFQUFFLElBQVMsRUFBRSxVQUErQixFQUFFO1FBQ2xGLE9BQU8sSUFBSSxPQUFPLENBQVMsQ0FBQyxPQUFPLEVBQUUsTUFBTSxFQUFFLEVBQUU7WUFDM0MsWUFBRSxDQUFDLFNBQVMsQ0FBQyxRQUFRLEVBQUUsSUFBSSxFQUFFLE9BQU8sRUFBRSxVQUFTLEdBQWlDO2dCQUM1RSxJQUFJLEdBQUcsRUFBRTtvQkFDTCxNQUFNLENBQUMsR0FBRyxDQUFDLENBQUM7aUJBQ2Y7cUJBQU07b0JBQ0gsT0FBTyxDQUFDLElBQUksQ0FBQyxRQUFRLEVBQUUsQ0FBQyxDQUFDO2lCQUM1QjtZQUNMLENBQUMsQ0FBQyxDQUFDO1FBQ1AsQ0FBQyxDQUFDLENBQUM7SUFDUCxDQUFDO0lBRU0sTUFBTSxDQUFDLFFBQVEsQ0FBQyxRQUFnQjtRQUNuQyxPQUFPLElBQUksT0FBTyxDQUFTLENBQUMsT0FBTyxFQUFFLE1BQU0sRUFBRSxFQUFFO1lBQzNDLFlBQUUsQ0FBQyxRQUFRLENBQUMsUUFBUSxFQUFFLFVBQVMsR0FBRyxFQUFFLElBQUk7Z0JBQ3BDLElBQUksR0FBRyxFQUFFO29CQUNMLE1BQU0sQ0FBQyxHQUFHLENBQUMsQ0FBQztpQkFDZjtxQkFBTTtvQkFDSCxPQUFPLENBQUMsSUFBSSxDQUFDLFFBQVEsRUFBRSxDQUFDLENBQUM7aUJBQzVCO1lBQ0wsQ0FBQyxDQUFDLENBQUE7UUFDTixDQUFDLENBQUMsQ0FBQztJQUNQLENBQUM7O0FBM0NzQixNQUFHLEdBQUcsUUFBRyxDQUFDO0FBT2pDO0lBREMsbUJBQVEsRUFBRTs2QkFTVjtBQWpCTCxxQkE4Q0MifQ==