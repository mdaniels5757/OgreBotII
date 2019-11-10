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
                    return "#ffabc5";
                case 4:
                    return "#a5b0ff";
                case 5:
                    return "#7996e2";
                case 6:
                    return "#6674de";
                case 7:
                    return "#584cde";
                case 8:
                    return "#3933e5";
                default:
                    return "#0D0596";
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
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiRWxlY3Rpb25SZXN1bHRzQ29sb3Jlci5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbIkVsZWN0aW9uUmVzdWx0c0NvbG9yZXIudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7QUFJYSxRQUFBLDZCQUE2QixHQUFHLENBQUMsS0FBaUIsRUFBRSxPQUFlLEVBQUUsRUFBRTtJQUNoRixRQUFRLEtBQUssRUFBRTtRQUNYLEtBQUssR0FBRztZQUNKLFFBQVEsSUFBSSxDQUFDLEtBQUssQ0FBQyxPQUFPLEdBQUcsRUFBRSxDQUFDLEVBQUU7Z0JBQzlCLEtBQUssQ0FBQyxDQUFDO2dCQUNQLEtBQUssQ0FBQyxDQUFDO2dCQUNQLEtBQUssQ0FBQyxDQUFDO2dCQUNQLEtBQUssQ0FBQztvQkFDRixPQUFPLFNBQVMsQ0FBQztnQkFDckIsS0FBSyxDQUFDO29CQUNGLE9BQU8sU0FBUyxDQUFDO2dCQUNyQixLQUFLLENBQUM7b0JBQ0YsT0FBTyxTQUFTLENBQUM7Z0JBQ3JCLEtBQUssQ0FBQztvQkFDRixPQUFPLFNBQVMsQ0FBQztnQkFDckIsS0FBSyxDQUFDO29CQUNGLE9BQU8sU0FBUyxDQUFDO2dCQUNyQixLQUFLLENBQUM7b0JBQ0YsT0FBTyxTQUFTLENBQUM7Z0JBQ3JCO29CQUNJLE9BQU8sU0FBUyxDQUFDO2FBQ3hCO1FBQ0wsS0FBSyxHQUFHO1lBQ0osUUFBUSxJQUFJLENBQUMsS0FBSyxDQUFDLE9BQU8sR0FBRyxFQUFFLENBQUMsRUFBRTtnQkFDOUIsS0FBSyxDQUFDLENBQUM7Z0JBQ1AsS0FBSyxDQUFDLENBQUM7Z0JBQ1AsS0FBSyxDQUFDLENBQUM7Z0JBQ1AsS0FBSyxDQUFDLENBQUM7Z0JBQ1AsS0FBSyxDQUFDO29CQUNGLE9BQU8sU0FBUyxDQUFDO2dCQUNyQixLQUFLLENBQUM7b0JBQ0YsT0FBTyxTQUFTLENBQUM7Z0JBQ3JCLEtBQUssQ0FBQztvQkFDRixPQUFPLFNBQVMsQ0FBQztnQkFDckIsS0FBSyxDQUFDO29CQUNGLE9BQU8sU0FBUyxDQUFDO2dCQUNyQixLQUFLLENBQUM7b0JBQ0YsT0FBTyxTQUFTLENBQUM7Z0JBQ3JCO29CQUNJLE9BQU8sU0FBUyxDQUFDO2FBQ3hCO1FBQ0wsS0FBSyxHQUFHO1lBQ0osT0FBTyxNQUFNLENBQUM7UUFDbEI7WUFDSSxNQUFNLElBQUksS0FBSyxDQUFDLHFDQUFxQyxDQUFDLENBQUM7S0FDOUQ7QUFDTCxDQUFDLENBQUEifQ==