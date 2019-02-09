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
    enqueue(action) {
        this.actions.push(action);
        this.run();
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
        if (this.ready && this.threadCount === 0) {
            this.resolve();
        }
    }
}
exports.default = MultiThreadedPromiseImpl;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoibXVsdGl0aHJlYWRlZC1wcm9taXNlLmpzIiwic291cmNlUm9vdCI6IiIsInNvdXJjZXMiOlsibXVsdGl0aHJlYWRlZC1wcm9taXNlLnRzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiI7O0FBTUEsTUFBcUIsd0JBQXdCO0lBVXpDLFlBQW9CLGFBQXFCLEVBQUU7UUFBdkIsZUFBVSxHQUFWLFVBQVUsQ0FBYTtRQVJuQyxZQUFPLEdBQWdDLEVBQUUsQ0FBQztRQUMxQyxnQkFBVyxHQUFXLENBQUMsQ0FBQztRQUN4QixZQUFPLEdBQWlCLEdBQUcsRUFBRSxHQUFHLENBQUMsQ0FBQztRQUNsQyxVQUFLLEdBQUcsS0FBSyxDQUFDO1FBQ2QsWUFBTyxHQUFzQixJQUFJLE9BQU8sQ0FBTyxPQUFPLENBQUMsRUFBRTtZQUM3RCxJQUFJLENBQUMsT0FBTyxHQUFHLE9BQU8sQ0FBQztRQUMzQixDQUFDLENBQUMsQ0FBQztJQUU0QyxDQUFDO0lBRWhELE9BQU8sQ0FBQyxNQUErQjtRQUNuQyxJQUFJLENBQUMsT0FBTyxDQUFDLElBQUksQ0FBQyxNQUFNLENBQUMsQ0FBQztRQUMxQixJQUFJLENBQUMsR0FBRyxFQUFFLENBQUM7SUFDZixDQUFDO0lBRUQsSUFBSTtRQUNBLElBQUksQ0FBQyxLQUFLLEdBQUcsSUFBSSxDQUFDO1FBQ2xCLElBQUksQ0FBQyxLQUFLLEVBQUUsQ0FBQztRQUNiLE9BQU8sSUFBSSxDQUFDLE9BQU8sQ0FBQztJQUN4QixDQUFDO0lBRU8sR0FBRztRQUNQLElBQUksSUFBSSxDQUFDLFdBQVcsR0FBRyxJQUFJLENBQUMsVUFBVSxFQUFFO1lBQ3BDLElBQUksTUFBTSxHQUFHLElBQUksQ0FBQyxPQUFPLENBQUMsS0FBSyxFQUFFLENBQUM7WUFDbEMsSUFBSSxNQUFNLEVBQUU7Z0JBQ1IsSUFBSSxDQUFDLFdBQVcsRUFBRSxDQUFDO2dCQUNuQixNQUFNLEVBQUUsQ0FBQyxJQUFJLENBQUMsR0FBRyxFQUFFO29CQUNmLElBQUksQ0FBQyxXQUFXLEVBQUUsQ0FBQztvQkFDbkIsSUFBSSxDQUFDLEtBQUssRUFBRSxDQUFBO29CQUNaLElBQUksQ0FBQyxHQUFHLEVBQUUsQ0FBQztnQkFDZixDQUFDLENBQUMsQ0FBQzthQUNOO1NBQ0o7SUFDTCxDQUFDO0lBRU8sS0FBSztRQUNULElBQUksSUFBSSxDQUFDLEtBQUssSUFBSSxJQUFJLENBQUMsV0FBVyxLQUFLLENBQUMsRUFBRTtZQUN0QyxJQUFJLENBQUMsT0FBTyxFQUFFLENBQUM7U0FDbEI7SUFDTCxDQUFDO0NBQ0o7QUExQ0QsMkNBMENDIn0=