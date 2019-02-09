"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const fs_1 = __importDefault(require("fs"));
const io_1 = __importDefault(require("../lib/io"));
const utils_1 = require("../lib/utils");
const LOG_DIR = `${io_1.default.projectDir}/log/category-files`;
const galleryNames = new Set();
(async function () {
    var files = fs_1.default.readdirSync(LOG_DIR);
    await Promise.all(fs_1.default.readdirSync(LOG_DIR).map(async (file) => {
        for (const [, match] of utils_1.matchAll(/^(.+?)\|/gm, await io_1.default.readFile(`${LOG_DIR}/${file}`))) {
            galleryNames.add(match);
        }
    }));
    console.log(JSON.stringify({
        dates: files.map(file => file.replace(/^(\d{4})(\d{2})(\d{2})\.log$/, "$1-$2-$3")).sort(),
        galleries: Array.from(galleryNames).sort(utils_1.sortCaseInsensitive)
    }));
}());
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiZ2V0LWdhbGxlcnktbmFtZXMuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJnZXQtZ2FsbGVyeS1uYW1lcy50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7OztBQUFBLDRDQUFvQjtBQUNwQixtREFBMkI7QUFDM0Isd0NBQTJEO0FBRTNELE1BQU0sT0FBTyxHQUFHLEdBQUcsWUFBRSxDQUFDLFVBQVUscUJBQXFCLENBQUM7QUFDdEQsTUFBTSxZQUFZLEdBQUcsSUFBSSxHQUFHLEVBQVUsQ0FBQztBQUN2QyxDQUFDLEtBQUs7SUFDRixJQUFJLEtBQUssR0FBRyxZQUFFLENBQUMsV0FBVyxDQUFDLE9BQU8sQ0FBQyxDQUFDO0lBQ3BDLE1BQU0sT0FBTyxDQUFDLEdBQUcsQ0FBQyxZQUFFLENBQUMsV0FBVyxDQUFDLE9BQU8sQ0FBQyxDQUFDLEdBQUcsQ0FBQyxLQUFLLEVBQUMsSUFBSSxFQUFDLEVBQUU7UUFDdkQsS0FBSyxNQUFNLENBQUMsRUFBRSxLQUFLLENBQUMsSUFBSSxnQkFBUSxDQUFDLFlBQVksRUFBRSxNQUFNLFlBQUUsQ0FBQyxRQUFRLENBQUMsR0FBRyxPQUFPLElBQUksSUFBSSxFQUFFLENBQUMsQ0FBQyxFQUFFO1lBQ3JGLFlBQVksQ0FBQyxHQUFHLENBQUMsS0FBSyxDQUFDLENBQUM7U0FDM0I7SUFDTCxDQUFDLENBQUMsQ0FBQyxDQUFDO0lBQ0osT0FBTyxDQUFDLEdBQUcsQ0FBQyxJQUFJLENBQUMsU0FBUyxDQUFDO1FBQ3ZCLEtBQUssRUFBRSxLQUFLLENBQUMsR0FBRyxDQUFDLElBQUksQ0FBQyxFQUFFLENBQUMsSUFBSSxDQUFDLE9BQU8sQ0FBQyw4QkFBOEIsRUFBRSxVQUFVLENBQUMsQ0FBQyxDQUFDLElBQUksRUFBRTtRQUN6RixTQUFTLEVBQUUsS0FBSyxDQUFDLElBQUksQ0FBQyxZQUFZLENBQUMsQ0FBQyxJQUFJLENBQUMsMkJBQW1CLENBQUM7S0FDaEUsQ0FBQyxDQUFDLENBQUM7QUFDUixDQUFDLEVBQUUsQ0FBQyxDQUFDIn0=