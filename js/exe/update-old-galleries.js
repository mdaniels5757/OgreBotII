"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const node_fetch_1 = __importDefault(require("node-fetch"));
const promiseUtils_1 = require("../lib/promiseUtils");
function zeroPad(value) {
    return String(value).padStart(2, "0");
}
function daysThisMonth(month, year) {
    switch (month) {
        case 1:
        case 3:
        case 5:
        case 7:
        case 8:
        case 10:
        case 12:
            return 31;
        case 4:
        case 6:
        case 9:
        case 11:
            return 30;
        case 2:
            return year % 4 ? 28 : 29;
        default:
            throw new Error("Sloppy developerment");
    }
}
function plusMinutes(dateString, minutes) {
    var [year, month, day, hour, minute] = [dateString.substr(0, 4), dateString.substr(4, 2), dateString.substr(6, 2),
        dateString.substr(8, 2), dateString.substr(10, 2)].map(s => +s);
    minute += minutes;
    while (minute > 59) {
        minute -= 60;
        hour++;
        while (hour > 23) {
            hour -= 24;
            day++;
            const daysThisMonthValue = daysThisMonth(month, year);
            while (day > daysThisMonthValue) {
                day -= daysThisMonthValue;
                month += 1;
                while (month > 12) {
                    month -= 12;
                    year++;
                }
            }
        }
    }
    return `${year}${zeroPad(month)}${zeroPad(day)}${zeroPad(hour)}${zeroPad(minute)}`;
}
const ranges = Object.entries({
    201311220000: 4,
    201505010000: 8,
    201605010000: 16,
    201906010000: 0
});
const [startDate = ranges[0][0], endDate = ranges[ranges.length - 1][0]] = process.argv.slice(2);
console.log(`Updating galleries from ${startDate} to ${endDate}`);
(async () => {
    for (var i = 0; i < ranges.length - 1; i++) {
        var [start, count] = ranges[i];
        const minutesBetween = 24 * 60 / count;
        const [untilDate] = ranges[i + 1];
        for (var date = start; date < untilDate; date = plusMinutes(date, minutesBetween)) {
            if (date >= startDate && date <= endDate) {
                console.log(`Updating ${date}`);
                //sometimes the script doesn't receive a response. Set a timeout for 5 minutes
                const timeout = new promiseUtils_1.SleepPromise(1000 * 60 * 5);
                await Promise.race([timeout.promise, node_fetch_1.default("https://tools.wmflabs.org/magog//UpdateNewUploads.php", {
                        method: 'POST',
                        body: `project=commons.wikimedia&start=${date}00`,
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                    })]);
                timeout.cancel();
                //no need to wait for processing the response
            }
        }
    }
})();
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoidXBkYXRlLW9sZC1nYWxsZXJpZXMuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJ1cGRhdGUtb2xkLWdhbGxlcmllcy50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7OztBQUFBLDREQUFtQztBQUNuQyxzREFBbUQ7QUFFbkQsU0FBUyxPQUFPLENBQUMsS0FBYTtJQUMxQixPQUFPLE1BQU0sQ0FBQyxLQUFLLENBQUMsQ0FBQyxRQUFRLENBQUMsQ0FBQyxFQUFFLEdBQUcsQ0FBQyxDQUFDO0FBQzFDLENBQUM7QUFDRCxTQUFTLGFBQWEsQ0FBQyxLQUFhLEVBQUUsSUFBWTtJQUM5QyxRQUFRLEtBQUssRUFBRTtRQUNYLEtBQUssQ0FBQyxDQUFDO1FBQ1AsS0FBSyxDQUFDLENBQUM7UUFDUCxLQUFLLENBQUMsQ0FBQztRQUNQLEtBQUssQ0FBQyxDQUFDO1FBQ1AsS0FBSyxDQUFDLENBQUM7UUFDUCxLQUFLLEVBQUUsQ0FBQztRQUNSLEtBQUssRUFBRTtZQUNILE9BQU8sRUFBRSxDQUFDO1FBQ2QsS0FBSyxDQUFDLENBQUM7UUFDUCxLQUFLLENBQUMsQ0FBQztRQUNQLEtBQUssQ0FBQyxDQUFDO1FBQ1AsS0FBSyxFQUFFO1lBQ0gsT0FBTyxFQUFFLENBQUM7UUFDZCxLQUFLLENBQUM7WUFDRixPQUFPLElBQUksR0FBRyxDQUFDLENBQUMsQ0FBQyxDQUFDLEVBQUUsQ0FBQyxDQUFDLENBQUMsRUFBRSxDQUFDO1FBQzlCO1lBQ0ksTUFBTSxJQUFJLEtBQUssQ0FBQyxzQkFBc0IsQ0FBQyxDQUFDO0tBQy9DO0FBQ0wsQ0FBQztBQUVELFNBQVMsV0FBVyxDQUFDLFVBQWtCLEVBQUUsT0FBZTtJQUNwRCxJQUFJLENBQUMsSUFBSSxFQUFFLEtBQUssRUFBRSxHQUFHLEVBQUUsSUFBSSxFQUFFLE1BQU0sQ0FBQyxHQUFHLENBQUMsVUFBVSxDQUFDLE1BQU0sQ0FBQyxDQUFDLEVBQUUsQ0FBQyxDQUFDLEVBQUUsVUFBVSxDQUFDLE1BQU0sQ0FBQyxDQUFDLEVB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