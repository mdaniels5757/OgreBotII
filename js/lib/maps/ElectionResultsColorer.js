"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.standardElectionResultColorer = (party, percent) => {
    switch (party) {
        case "d":
            switch (Math.floor(percent * 10)) {
                case 0:
                case 1:
                case 2:
                case 3:
                case 4:
                    return "#a5b0ff";
                case 5:
                    return "#7996e2";
                case 6:
                    return "#6674de";
                case 7:
                    return "#584cde";
                default:
                    return "#3933e5";
            }
        case "r":
            switch (Math.floor(percent * 10)) {
                case 0:
                case 1:
                case 2:
                case 3:
                case 4:
                    return "#ffb2b2";
                case 5:
                    return "#e27f7f";
                case 6:
                    return "#d75d5d";
                case 7:
                    return "#d72f30";
                case 8:
                    return "#c21b18";
                default:
                    return "#a80000";
            }
        case "t":
            return "#888";
        default:
            throw new Error("Can't currently handle independents");
    }
};
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiRWxlY3Rpb25SZXN1bHRzQ29sb3Jlci5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbIkVsZWN0aW9uUmVzdWx0c0NvbG9yZXIudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7QUFJYSxRQUFBLDZCQUE2QixHQUFHLENBQUMsS0FBaUIsRUFBRSxPQUFlLEVBQUUsRUFBRTtJQUNoRixRQUFRLEtBQUssRUFBRTtRQUNYLEtBQUssR0FBRztZQUNKLFFBQVEsSUFBSSxDQUFDLEtBQUssQ0FBQyxPQUFPLEdBQUcsRUFBRSxDQUFDLEVBQUU7Z0JBQzlCLEtBQUssQ0FBQyxDQUFDO2dCQUNQLEtBQUssQ0FBQyxDQUFDO2dCQUNQLEtBQUssQ0FBQyxDQUFDO2dCQUNQLEtBQUssQ0FBQyxDQUFDO2dCQUNQLEtBQUssQ0FBQztvQkFDRixPQUFPLFNBQVMsQ0FBQztnQkFDckIsS0FBSyxDQUFDO29CQUNGLE9BQU8sU0FBUyxDQUFDO2dCQUNyQixLQUFLLENBQUM7b0JBQ0YsT0FBTyxTQUFTLENBQUM7Z0JBQ3JCLEtBQUssQ0FBQztvQkFDRixPQUFPLFNBQVMsQ0FBQztnQkFDckI7b0JBQ0ksT0FBTyxTQUFTLENBQUM7YUFDeEI7UUFDTCxLQUFLLEdBQUc7WUFDSixRQUFRLElBQUksQ0FBQyxLQUFLLENBQUMsT0FBTyxHQUFHLEVBQUUsQ0FBQyxFQUFFO2dCQUM5QixLQUFLLENBQUMsQ0FBQztnQkFDUCxLQUFLLENBQUMsQ0FBQztnQkFDUCxLQUFLLENBQUMsQ0FBQztnQkFDUCxLQUFLLENBQUMsQ0FBQztnQkFDUCxLQUFLLENBQUM7b0JBQ0YsT0FBTyxTQUFTLENBQUM7Z0JBQ3JCLEtBQUssQ0FBQztvQkFDRixPQUFPLFNBQVMsQ0FBQztnQkFDckIsS0FBSyxDQUFDO29CQUNGLE9BQU8sU0FBUyxDQUFDO2dCQUNyQixLQUFLLENBQUM7b0JBQ0YsT0FBTyxTQUFTLENBQUM7Z0JBQ3JCLEtBQUssQ0FBQztvQkFDRixPQUFPLFNBQVMsQ0FBQztnQkFDckI7b0JBQ0ksT0FBTyxTQUFTLENBQUM7YUFDeEI7UUFDTCxLQUFLLEdBQUc7WUFDSixPQUFPLE1BQU0sQ0FBQztRQUNsQjtZQUNJLE1BQU0sSUFBSSxLQUFLLENBQUMscUNBQXFDLENBQUMsQ0FBQztLQUM5RDtBQUNMLENBQUMsQ0FBQSJ9