const IDENT_COOKIE_NAME = "magog-ident";
const topWindow = window.top;
const parentLocation = topWindow.location;
var watch;

if (location.pathname.endsWith("/complete.php")) {
    window.history.pushState("", "", "start.php");
}

angular
    .module("app", ["ngCookies"])
    .controller("ctrl", [
        "$scope",
        "$http",
        "$log",
        "$interval",
        "$cookies",
        ($scope, $http, $log, $interval, $cookies) => {
            function getCookie() {
                return $cookies.get(IDENT_COOKIE_NAME);
            }

            function postMessage(state) {
                if (watch) {
                    topWindow.postMessage({ state: state, cookie: $scope.cookie }, location.origin);
                }
            }

            angular.extend($scope, {
                async authorize() {
                    $scope.ajax = true;
                    let response = await $http({ url: "authorize.php" });

                    let redirect = response.data.redirect;
                    $scope.ajax = false;
                    if (redirect) {
                        parentLocation.href = redirect;
                    }
                },
                async logout() {
                    $scope.ajax = true;
                    let response = await $http({ url: "logout.php" });

                    $scope.ajax = false;
                    if (response.data.success) {
                        $scope.username = $scope.cookie = null;
                    }
                },
                open() {
                    window.open(location.pathname, "_blank");
                }
            });

            $scope.cookie = getCookie();

            $interval(() => {
                let newCookie = getCookie();
                if (newCookie !== $scope.cookie) {
                    $scope.username = null;
                    $scope.cookie = newCookie;
                    postMessage("update");
                }
            }, 2000);

            window.onmessage = event => {
                let originSource = event.origin;

                if (originSource !== location.origin) {
                    $log.error(`Bad origin: ${originSource} != ${location.origin}`);
                    return;
                }

                watch = (event.data || {}).watch;
                if (watch) {
                    postMessage("initial");
                }
            };
        }
    ])
    .filter("escape", () => url =>
        window
            .encodeURIComponent(url)
            .replace(/%2F/g, "/")
            .replace(/%3A/g, ":")
            .replace(/%20/g, "_")
    );
