"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const node_fetch_1 = __importDefault(require("node-fetch"));
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
                await node_fetch_1.default("https://tools.wmflabs.org/magog//UpdateNewUploads.php", {
                    method: 'POST',
                    body: `project=commons.wikimedia&start=${date}00`,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                });
                //no need to wait for processing the response
            }
        }
    }
})();
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoidXBkYXRlLW9sZC1nYWxsZXJpZXMuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJ1cGRhdGUtb2xkLWdhbGxlcmllcy50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7OztBQUFBLDREQUFtQztBQUVuQyxTQUFTLE9BQU8sQ0FBQyxLQUFhO0lBQzFCLE9BQU8sTUFBTSxDQUFDLEtBQUssQ0FBQyxDQUFDLFFBQVEsQ0FBQyxDQUFDLEVBQUUsR0FBRyxDQUFDLENBQUM7QUFDMUMsQ0FBQztBQUNELFNBQVMsYUFBYSxDQUFDLEtBQWEsRUFBRSxJQUFZO0lBQzlDLFFBQVEsS0FBSyxFQUFFO1FBQ1gsS0FBSyxDQUFDLENBQUM7UUFDUCxLQUFLLENBQUMsQ0FBQztRQUNQLEtBQUssQ0FBQyxDQUFDO1FBQ1AsS0FBSyxDQUFDLENBQUM7UUFDUCxLQUFLLENBQUMsQ0FBQztRQUNQLEtBQUssRUFBRSxDQUFDO1FBQ1IsS0FBSyxFQUFFO1lBQ0gsT0FBTyxFQUFFLENBQUM7UUFDZCxLQUFLLENBQUMsQ0FBQztRQUNQLEtBQUssQ0FBQyxDQUFDO1FBQ1AsS0FBSyxDQUFDLENBQUM7UUFDUCxLQUFLLEVBQUU7WUFDSCxPQUFPLEVBQUUsQ0FBQztRQUNkLEtBQUssQ0FBQztZQUNGLE9BQU8sSUFBSSxHQUFHLENBQUMsQ0FBQyxDQUFDLENBQUMsRUFBRSxDQUFDLENBQUMsQ0FBQyxFQUFFLENBQUM7UUFDOUI7WUFDSSxNQUFNLElBQUksS0FBSyxDQUFDLHNCQUFzQixDQUFDLENBQUM7S0FDL0M7QUFDTCxDQUFDO0FBRUQsU0FBUyxXQUFXLENBQUMsVUFBa0IsRUFBRSxPQUFlO0lBQ3BELElBQUksQ0FBQyxJQUFJLEVBQUUsS0FBSyxFQUFFLEdBQUcsRUFBRSxJQUFJLEVBQUUsTUFBTSxDQUFDLEdBQUcsQ0FBQyxVQUFVLENBQUMsTUFBTSxDQUFDLENBQUMsRUFBRSxDQUFDLENBQUMsRUFBRSxVQUFVLENBQUMsTUFBTSxDQUFDLENBQUMsRUFBRSxDQUFDLENBQUMs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