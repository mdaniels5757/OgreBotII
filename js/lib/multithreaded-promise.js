"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
class MultiThreadedPromiseImpl {
    constructor(maxThreads = 20) {
        this.maxThreads = maxThreads;
        this.actions = [];
        this.threadCount = 0;
        this.resolve = () => { };
        this.ready = false;
        this.promise = new Promise(resolve => {
            this.resolve = resolve;
        });
    }
    enqueue(...actions) {
        for (const action of actions) {
            this.actions.push(action);
            this.run();
        }
    }
    done() {
        this.ready = true;
        this.check();
        return this.promise;
    }
    run() {
        if (this.threadCount < this.maxThreads) {
            let action = this.actions.shift();
            if (action) {
                this.threadCount++;
                action().then(() => {
                    this.threadCount--;
                    this.check();
                    this.run();
                });
            }
        }
    }
    check() {
        if (this.ready && this.threadCount === 0 && this.actions.length === 0) {
            this.resolve();
        }
    }
}
exports.default = MultiThreadedPromiseImpl;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoibXVsdGl0aHJlYWRlZC1wcm9taXNlLmpzIiwic291cmNlUm9vdCI6IiIsInNvdXJjZXMiOlsibXVsdGl0aHJlYWRlZC1wcm9taXNlLnRzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiI7O0FBTUEsTUFBcUIsd0JBQXdCO0lBVXpDLFlBQW9CLGFBQXFCLEVBQUU7UUFBdkIsZUFBVSxHQUFWLFVBQVUsQ0FBYTtRQVJuQyxZQUFPLEdBQWdDLEVBQUUsQ0FBQztRQUMxQyxnQkFBVyxHQUFXLENBQUMsQ0FBQztRQUN4QixZQUFPLEdBQWlCLEdBQUcsRUFBRSxHQUFHLENBQUMsQ0FBQztRQUNsQyxVQUFLLEdBQUcsS0FBSyxDQUFDO1FBQ2QsWUFBTyxHQUFzQixJQUFJLE9BQU8sQ0FBTyxPQUFPLENBQUMsRUFBRTtZQUM3RCxJQUFJLENBQUMsT0FBTyxHQUFHLE9BQU8sQ0FBQztRQUMzQixDQUFDLENBQUMsQ0FBQztJQUU0QyxDQUFDO0lBRWhELE9BQU8sQ0FBQyxHQUFHLE9BQW9DO1FBQzNDLEtBQUssTUFBTSxNQUFNLElBQUksT0FBTyxFQUFFO1lBQzFCLElBQUksQ0FBQyxPQUFPLENBQUMsSUFBSSxDQUFDLE1BQU0sQ0FBQyxDQUFDO1lBQzFCLElBQUksQ0FBQyxHQUFHLEVBQUUsQ0FBQztTQUNkO0lBQ0wsQ0FBQztJQUVELElBQUk7UUFDQSxJQUFJLENBQUMsS0FBSyxHQUFHLElBQUksQ0FBQztRQUNsQixJQUFJLENBQUMsS0FBSyxFQUFFLENBQUM7UUFDYixPQUFPLElBQUksQ0FBQyxPQUFPLENBQUM7SUFDeEIsQ0FBQztJQUVPLEdBQUc7UUFDUCxJQUFJLElBQUksQ0FBQyxXQUFXLEdBQUcsSUFBSSxDQUFDLFVBQVUsRUFBRTtZQUNwQyxJQUFJLE1BQU0sR0FBRyxJQUFJLENBQUMsT0FBTyxDQUFDLEtBQUssRUFBRSxDQUFDO1lBQ2xDLElBQUksTUFBTSxFQUFFO2dCQUNSLElBQUksQ0FBQyxXQUFXLEVBQUUsQ0FBQztnQkFDbkIsTUFBTSxFQUFFLENBQUMsSUFBSSxDQUFDLEdBQUcsRUFBRTtvQkFDZixJQUFJLENBQUMsV0FBVyxFQUFFLENBQUM7b0JBQ25CLElBQUksQ0FBQyxLQUFLLEVBQUUsQ0FBQTtvQkFDWixJQUFJLENBQUMsR0FBRyxFQUFFLENBQUM7Z0JBQ2YsQ0FBQyxDQUFDLENBQUM7YUFDTjtTQUNKO0lBQ0wsQ0FBQztJQUVPLEtBQUs7UUFDVCxJQUFJLElBQUksQ0FBQyxLQUFLLElBQUksSUFBSSxDQUFDLFdBQVcsS0FBSyxDQUFDLElBQUksSUFBSSxDQUFDLE9BQU8sQ0FBQyxNQUFNLEtBQUssQ0FBQyxFQUFFO1lBQ25FLElBQUksQ0FBQyxPQUFPLEVBQUUsQ0FBQztTQUNsQjtJQUNMLENBQUM7Q0FDSjtBQTVDRCwyQ0E0Q0MifQ==