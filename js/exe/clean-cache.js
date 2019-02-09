"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const mediawiki_1 = __importDefault(require("../lib/mediawiki"));
const multithreaded_promise_1 = __importDefault(require("../lib/multithreaded-promise"));
const [category, numberOfThreads = 45] = process.argv.slice(2);
if (!category) {
    throw new Error("Category name required");
}
const mw = mediawiki_1.default();
(async function () {
    let i = 0;
    do {
        const multithread = new multithreaded_promise_1.default(+numberOfThreads);
        var members = await mw.categoryMembers(category);
        for (const member of members) {
            multithread.enqueue(async () => {
                if (member) {
                    console.log(`Purging #${++i}`);
                    return await mw.editAppend(member, "", "\n\n");
                }
            });
        }
        await multithread.done();
    } while (members.length > 500);
}());
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiY2xlYW4tY2FjaGUuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJjbGVhbi1jYWNoZS50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7OztBQUNBLGlFQUE0QztBQUM1Qyx5RkFBZ0U7QUFFaEUsTUFBTSxDQUFDLFFBQVEsRUFBRSxlQUFlLEdBQUcsRUFBRSxDQUFDLEdBQUksT0FBTyxDQUFDLElBQUksQ0FBQyxLQUFLLENBQUMsQ0FBQyxDQUFDLENBQUM7QUFFaEUsSUFBSSxDQUFDLFFBQVEsRUFBRTtJQUNYLE1BQU0sSUFBSSxLQUFLLENBQUMsd0JBQXdCLENBQUMsQ0FBQztDQUM3QztBQUVELE1BQU0sRUFBRSxHQUFHLG1CQUFZLEVBQUUsQ0FBQztBQUMxQixDQUFDLEtBQUs7SUFDRixJQUFJLENBQUMsR0FBRyxDQUFDLENBQUM7SUFDVixHQUFHO1FBQ0MsTUFBTSxXQUFXLEdBQUcsSUFBSSwrQkFBb0IsQ0FBQyxDQUFDLGVBQWUsQ0FBQyxDQUFDO1FBQy9ELElBQUksT0FBTyxHQUFHLE1BQU0sRUFBRSxDQUFDLGVBQWUsQ0FBQyxRQUFRLENBQUMsQ0FBQztRQUNqRCxLQUFLLE1BQU0sTUFBTSxJQUFJLE9BQU8sRUFBRTtZQUMxQixXQUFXLENBQUMsT0FBTyxDQUFDLEtBQUssSUFBSSxFQUFFO2dCQUMzQixJQUFJLE1BQU0sRUFBRTtvQkFDUixPQUFPLENBQUMsR0FBRyxDQUFDLFlBQVksRUFBRSxDQUFDLEVBQUUsQ0FBQyxDQUFDO29CQUMvQixPQUFPLE1BQU0sRUFBRSxDQUFDLFVBQVUsQ0FBQyxNQUFNLEVBQUUsRUFBRSxFQUFFLE1BQU0sQ0FBQyxDQUFDO2lCQUNsRDtZQUNMLENBQUMsQ0FBQyxDQUFDO1NBQ047UUFDRCxNQUFNLFdBQVcsQ0FBQyxJQUFJLEVBQUUsQ0FBQztLQUM1QixRQUFRLE9BQU8sQ0FBQyxNQUFNLEdBQUcsR0FBRyxFQUFFO0FBQ25DLENBQUMsRUFBRSxDQUFDLENBQUMifQ==