/* global angular */
((window) => {
    function retrieveGetParameters() {
        const searchString = window.location.search.substring(1);
        var searchObject = {};

        if (searchString) {
            searchString.split("&").forEach((param) => {
                let [name, val] = param.split("=");
                if (!searchObject[name]) {
                    searchObject[name] = window.decodeURIComponent(val);
                }
            });
        }

        return searchObject;
    }

    angular
        .module("app", [])
        .controller("ctrl", ["$scope", "$http", ($scope, $http) => {
            const parametersFromUrl = retrieveGetParameters();

            var lastSearchedDate;
            var allDateEntries;

            Object.assign($scope, {
                selectedDate: parametersFromUrl.date,
                selectedGallery: parametersFromUrl.gallery,
                dates: [],
                galleries: [],
                entries: [],
                limit: 100,
                loadData: async () => {
                    function updateEntries() {
                        const localEntries = selectedGallery ? allDateEntries.filter(
                            entry => entry.gallery === selectedGallery
                        ) : allDateEntries;

                        $scope.entries = $scope.limit ? localEntries.slice(0, $scope.limit) : localEntries;
                        $scope.$digest();
                    }

                    const selectedDate = $scope.selectedDate || "";
                    const selectedGallery = $scope.selectedGallery;

                    if (lastSearchedDate !== selectedDate) {
                        $scope.loading = true;
                        lastSearchedDate = selectedDate;

                        const data = (await $http({
                            url: `category-files-blame-data.php?date=${lastSearchedDate}`
                        })).data;


                        allDateEntries = data.entries;
                        $scope.loading = false;
                        $scope.galleries = data.galleries;
                        const dates = $scope.dates = data.dates.reverse();
                        $scope.selectedDate = $scope.selectedDate || dates[0];

                        updateEntries();
                    } else {
                        updateEntries();
                    }
                }
            });

            $scope.loadData();
        }])
        .filter("escape", () => (url) =>
            window.encodeURIComponent(url).replace(/%2F/g, "/")
            .replace(/%3A/g, ":").replace(/%20/g, "_")
        );

    //TODO remove me when Closure correctly polyfills promises
    return Promise;
})(window);