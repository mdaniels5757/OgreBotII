export const awaitReady = () => <T extends { ready: Promise<void> }>(t: T, _property: string, propertyDescriptor: PropertyDescriptor) => {
    const originalFunction = propertyDescriptor.value;
    propertyDescriptor.value = async function (this: T) {
        await this.ready;
        return originalFunction.apply(this, arguments);
    };
};