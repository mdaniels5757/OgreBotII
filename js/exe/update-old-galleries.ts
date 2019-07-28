import nodeFetch from 'node-fetch';

function zeroPad(value: number) {
    return String(value).padStart(2, "0");
}
function daysThisMonth(month: number, year: number) {
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

function plusMinutes(dateString: string, minutes: number) {
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
                await nodeFetch("https://tools.wmflabs.org/magog//UpdateNewUploads.php", {
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