"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const fs_1 = __importDefault(require("fs"));
const utils_1 = require("./utils");
class Io {
    static getProperty(file, property) {
        let thisProperties = this.properties.get(file);
        if (!thisProperties) {
            thisProperties = new Map();
            const contents = fs_1.default.readFileSync(`${__dirname}/../properties/${file}.properties`, { encoding: "UTF-8" });
            for (const [, key, val] of utils_1.matchAll(() => new RegExp(/^\s*(.+?)\s*\=\s*"?(.+)"\s*?$/gm), contents)) {
                thisProperties.set(key, val);
            }
            this.properties.set(file, thisProperties);
        }
        return thisProperties.get(property);
    }
}
Io.properties = new Map();
exports.default = Io;
//# sourceMappingURL=io.js.map