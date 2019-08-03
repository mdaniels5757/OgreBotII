"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
class ThreadPoolImpl {
    constructor(maxThreads = 20) {
        this.maxThreads = maxThreads;
        this.actions = [];
        this._threadCount = 0;
    }
    enqueue(...actions) {
        return new Promise(resolve => {
            var myThreadCount = actions.length;
            this.actions.push(...actions.map(action => async () => {
                await action();
                this._threadCount--;
                if (--myThreadCount === 0) {
                    resolve();
                }
                this.run();
            }));
            this.run();
        });
    }
    enqueueAll(actions) {
        return this.enqueue(...actions());
    }
    run() {
        var action;
        while (this._threadCount < this.maxThreads && (action = this.actions.shift())) {
            this._threadCount++;
            action();
        }
    }
}
exports.ThreadPoolImpl = ThreadPoolImpl;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiVGhyZWFkUG9vbC5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbIlRocmVhZFBvb2wudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7QUFLQSxNQUFhLGNBQWM7SUFLdkIsWUFBb0IsYUFBcUIsRUFBRTtRQUF2QixlQUFVLEdBQVYsVUFBVSxDQUFhO1FBSG5DLFlBQU8sR0FBZ0MsRUFBRSxDQUFDO1FBQzFDLGlCQUFZLEdBQVcsQ0FBQyxDQUFDO0lBRWMsQ0FBQztJQUVoRCxPQUFPLENBQUMsR0FBRyxPQUFvQztRQUMzQyxPQUFPLElBQUksT0FBTyxDQUFDLE9BQU8sQ0FBQyxFQUFFO1lBQ3pCLElBQUksYUFBYSxHQUFHLE9BQU8sQ0FBQyxNQUFNLENBQUM7WUFDbkMsSUFBSSxDQUFDLE9BQU8sQ0FBQyxJQUFJLENBQUMsR0FBRyxPQUFPLENBQUMsR0FBRyxDQUFDLE1BQU0sQ0FBQyxFQUFFLENBQUMsS0FBSyxJQUFHLEVBQUU7Z0JBQ2pELE1BQU0sTUFBTSxFQUFFLENBQUM7Z0JBQ2YsSUFBSSxDQUFDLFlBQVksRUFBRSxDQUFDO2dCQUNwQixJQUFJLEVBQUUsYUFBYSxLQUFLLENBQUMsRUFBRTtvQkFDdkIsT0FBTyxFQUFFLENBQUM7aUJBQ2I7Z0JBQ0QsSUFBSSxDQUFDLEdBQUcsRUFBRSxDQUFDO1lBQ2YsQ0FBQyxDQUFDLENBQUMsQ0FBQztZQUNKLElBQUksQ0FBQyxHQUFHLEVBQUUsQ0FBQztRQUNmLENBQUMsQ0FBQyxDQUFDO0lBQ1AsQ0FBQztJQUVELFVBQVUsQ0FBQyxPQUFrRDtRQUN6RCxPQUFPLElBQUksQ0FBQyxPQUFPLENBQUMsR0FBRyxPQUFPLEVBQUUsQ0FBQyxDQUFDO0lBQ3RDLENBQUM7SUFHTyxHQUFHO1FBQ1AsSUFBSSxNQUFNLENBQUM7UUFDWCxPQUFPLElBQUksQ0FBQyxZQUFZLEdBQUcsSUFBSSxDQUFDLFVBQVUsSUFBSSxDQUFDLE1BQU0sR0FBRyxJQUFJLENBQUMsT0FBTyxDQUFDLEtBQUssRUFBRSxDQUFDLEVBQUU7WUFDM0UsSUFBSSxDQUFDLFlBQVksRUFBRSxDQUFDO1lBQ3BCLE1BQU0sRUFBRSxDQUFDO1NBQ1o7SUFDTCxDQUFDO0NBQ0o7QUFsQ0Qsd0NBa0NDIn0=