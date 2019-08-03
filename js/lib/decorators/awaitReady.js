"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.awaitReady = () => (t, _property, propertyDescriptor) => {
    const originalFunction = propertyDescriptor.value;
    propertyDescriptor.value = async function () {
        await this.ready;
        return originalFunction.apply(this, arguments);
    };
};
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiYXdhaXRSZWFkeS5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbImF3YWl0UmVhZHkudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7QUFBYSxRQUFBLFVBQVUsR0FBRyxHQUFHLEVBQUUsQ0FBQyxDQUFxQyxDQUFJLEVBQUUsU0FBaUIsRUFBRSxrQkFBc0MsRUFBRSxFQUFFO0lBQ3BJLE1BQU0sZ0JBQWdCLEdBQUcsa0JBQWtCLENBQUMsS0FBSyxDQUFDO0lBQ2xELGtCQUFrQixDQUFDLEtBQUssR0FBRyxLQUFLO1FBQzVCLE1BQU0sSUFBSSxDQUFDLEtBQUssQ0FBQztRQUNqQixPQUFPLGdCQUFnQixDQUFDLEtBQUssQ0FBQyxJQUFJLEVBQUUsU0FBUyxDQUFDLENBQUM7SUFDbkQsQ0FBQyxDQUFDO0FBQ04sQ0FBQyxDQUFDIn0=