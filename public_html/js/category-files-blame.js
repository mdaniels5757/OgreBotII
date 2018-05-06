/* global angular */
(window => {
    function retrieveGetParameters() {
        const searchString = window.location.search.substring(1);
        var searchObject = {};

        if (searchString) {
            searchString.split("&").forEach(param => {
                let [ name, val ] = param.split("=");
                if (!searchObject[name]) {
                    searchObject[name] = window.decodeURIComponent(val);
                }
            });
        }

        return searchObject;
    }

    angular.module("app", []).controller("ctrl", [
        "$scope",
        "$http",
        "$timeout",
        async ($scope, $http, $timeout) => {
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
                async loadData() {
                    function updateEntries() {
                        const localEntries = selectedGallery
                            ? allDateEntries.filter(entry => entry.gallery === selectedGallery)
                            : allDateEntries;

                        $scope.entries = $scope.limit
                            ? localEntries.slice(0, $scope.limit)
                            : localEntries;
                        $scope.$digest();
                    }

                    const selectedDate = $scope.selectedDate || "";
                    const selectedGallery = $scope.selectedGallery;

                    if (lastSearchedDate !== selectedDate) {
                        $scope.loading = true;
                        lastSearchedDate = selectedDate;

                        var response = await $http({
                            url: `category-files-blame-data.php?date=${lastSearchedDate}`
                        });
                        allDateEntries = response.data;

                        $scope.loading = false;
                        $scope.selectedDate = $scope.selectedDate || $scope.dates[0];

                        updateEntries();
                    } else {
                        updateEntries();
                    }
                }
            });

            if ($scope.selectedDate) {
                $scope.loadData();
            } else {
                await $timeout();
                $scope.selectedDate = $scope.dates[0];
                $scope.$digest();
            }
        }
    ]).filter("escape", () =>
        url =>
            window
                .encodeURIComponent(url)
                .replace(/%2F/g, "/")
                .replace(/%3A/g, ":")
                .replace(/%20/g, "_"));
})(window);
