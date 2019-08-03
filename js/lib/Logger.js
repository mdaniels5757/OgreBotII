"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const fs_1 = __importDefault(require("fs"));
var LogLevel;
(function (LogLevel) {
    LogLevel["trace"] = "1";
    LogLevel["debug"] = "2";
    LogLevel["info"] = "3";
    LogLevel["warn"] = "4";
    LogLevel["error"] = "5";
})(LogLevel || (LogLevel = {}));
;
const processMatch = process.argv[1].match(/\/([^\/]+?)\.js/);
if (!processMatch) {
    throw new Error("Can't determine process name");
}
console.log(processMatch[1]);
const logLevelString = JSON.parse(fs_1.default.readFileSync(`${__dirname}/../config.json`).
    toString())["log-level"][processMatch[1]].toLowerCase();
const myLevelLevel = +LogLevel[logLevelString];
if (myLevelLevel === undefined) {
    throw new Error(`Unrecognized log level: ${logLevelString}`);
}
const logger = Object.fromEntries(function* () {
    for (const [entry, level] of Object.entries(LogLevel)) {
        const enabled = myLevelLevel <= level;
        yield [`${entry}Enabled`, enabled];
        yield [entry, enabled ? console[(entry !== "trace" ? entry : "debug")] : function () { }];
    }
}());
logger.debug(`Logger loaded with level ${logLevelString}`);
exports.default = logger;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiTG9nZ2VyLmpzIiwic291cmNlUm9vdCI6IiIsInNvdXJjZXMiOlsiTG9nZ2VyLnRzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiI7Ozs7O0FBQUEsNENBQW9CO0FBRXBCLElBQUssUUFNSjtBQU5ELFdBQUssUUFBUTtJQUNULHVCQUFXLENBQUE7SUFDWCx1QkFBVyxDQUFBO0lBQ1gsc0JBQVUsQ0FBQTtJQUNWLHNCQUFVLENBQUE7SUFDVix1QkFBVyxDQUFBO0FBQ2YsQ0FBQyxFQU5JLFFBQVEsS0FBUixRQUFRLFFBTVo7QUFBQSxDQUFDO0FBRUYsTUFBTSxZQUFZLEdBQUcsT0FBTyxDQUFDLElBQUksQ0FBQyxDQUFDLENBQUMsQ0FBQyxLQUFLLENBQUMsaUJBQWlCLENBQUMsQ0FBQztBQUM5RCxJQUFJLENBQUMsWUFBWSxFQUFFO0lBQ2YsTUFBTSxJQUFJLEtBQUssQ0FBQyw4QkFBOEIsQ0FBQyxDQUFDO0NBQ25EO0FBRUQsT0FBTyxDQUFDLEdBQUcsQ0FBQyxZQUFZLENBQUMsQ0FBQyxDQUFDLENBQUMsQ0FBQztBQUM3QixNQUFNLGNBQWMsR0FBRyxJQUFJLENBQUMsS0FBSyxDQUFDLFlBQUUsQ0FBQyxZQUFZLENBQUMsR0FBRyxTQUFTLGlCQUFpQixDQUFDO0lBQzVFLFFBQVEsRUFBRSxDQUFDLENBQUMsV0FBVyxDQUFDLENBQUMsWUFBWSxDQUFDLENBQUMsQ0FBQyxDQUFDLENBQUMsV0FBVyxFQUFFLENBQUM7QUFDNUQsTUFBTSxZQUFZLEdBQUcsQ0FBQyxRQUFRLENBQUMsY0FBYyxDQUFDLENBQUM7QUFDL0MsSUFBSSxZQUFZLEtBQUssU0FBUyxFQUFFO0lBQzVCLE1BQU0sSUFBSSxLQUFLLENBQUMsMkJBQTJCLGNBQWMsRUFBRSxDQUFDLENBQUM7Q0FDaEU7QUFjRCxNQUFNLE1BQU0sR0FBVyxNQUFNLENBQUMsV0FBVyxDQUFDLFFBQVEsQ0FBQztJQUMvQyxLQUFLLE1BQU0sQ0FBQyxLQUFLLEVBQUUsS0FBSyxDQUFDLElBQXVELE1BQU0sQ0FBQyxPQUFPLENBQUMsUUFBUSxDQUFDLEVBQUU7UUFDdEcsTUFBTSxPQUFPLEdBQUksWUFBWSxJQUFJLEtBQUssQ0FBQztRQUN2QyxNQUFNLENBQUMsR0FBRyxLQUFLLFNBQVMsRUFBRSxPQUFPLENBQUMsQ0FBQztRQUNuQyxNQUFNLENBQUMsS0FBSyxFQUFFLE9BQU8sQ0FBQyxDQUFDLENBQUMsT0FBTyxDQUFDLENBQUMsS0FBSyxLQUFLLE9BQU8sQ0FBQyxDQUFDLENBQUMsS0FBSyxDQUFDLENBQUMsQ0FBQyxPQUFPLENBQUMsQ0FBQyxDQUFDLENBQUMsQ0FBQyxjQUFjLENBQUMsQ0FBQyxDQUFDO0tBQzdGO0FBQ0wsQ0FBQyxFQUFFLENBQUMsQ0FBQztBQUVMLE1BQU0sQ0FBQyxLQUFLLENBQUMsNEJBQTRCLGNBQWMsRUFBRSxDQUFDLENBQUM7QUFFM0Qsa0JBQWUsTUFBTSxDQUFDIn0=