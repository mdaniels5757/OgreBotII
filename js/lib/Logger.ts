import fs from "fs";

enum LogLevel {
    trace = "1",
    debug = "2",
    info = "3",
    warn = "4",
    error = "5"
};

const processMatch = process.argv[1].match(/\/([^\/]+?)\.js/);
if (!processMatch) {
    throw new Error("Can't determine process name");
}

console.log(processMatch[1]);
const logLevelString = JSON.parse(fs.readFileSync(`${__dirname}/../config.json`).
    toString())["log-level"][processMatch[1]].toLowerCase();
const myLevelLevel = +LogLevel[logLevelString];
if (myLevelLevel === undefined) {
    throw new Error(`Unrecognized log level: ${logLevelString}`);
}
interface Logger {
    readonly traceEnabled: boolean;
    readonly debugEnabled: boolean;
    readonly infoEnabled: boolean;
    readonly warnEanbled: boolean;
    readonly errorEnabled: boolean;
    trace(...args: any[]) : void;
    debug(...args: any[]) : void;
    info(...args: any[]) : void;
    warn(...args: any[]) : void;
    error(...args: any[]) : void;
}

const logger = <Logger>Object.fromEntries(function*(){
    for (const [entry, level] of <["trace"|"debug"|"info"|"warn"|"error", number][]>Object.entries(LogLevel)) {
        const enabled =  myLevelLevel <= level;
        yield [`${entry}Enabled`, enabled];
        yield [entry, enabled ? console[(entry !== "trace" ? entry : "debug")] : function () { }];
    }
}());

logger.debug(`Logger loaded with level ${logLevelString}`);

export default logger;