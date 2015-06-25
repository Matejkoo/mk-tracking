var app = angular.module("mkTrackingApp", ["angular.filter","highcharts-ng"]);

app.controller("listCtrl", function ($scope, $http) {

    $scope.orderByField = 'event_id';
    $scope.reverseSort = false;
    $scope.countItems = [];
    $scope.addNames = [];

    $scope.addCount = function(cnt) {
        $scope.countItems.push(parseInt(cnt));
        return true;
    };

    $scope.addName = function(name) {
        if( $scope.addNames.indexOf(name) == -1) {
            $scope.addNames.push(name);
            return true;
        } else return false;
    };

    $http({
        method: 'GET',
        url: 'http://mateuszkolasa.pl/hire/wp-content/plugins/mk-tracking/api.php',
        data: {}
    }).success(function (response) {
        $scope.list = response;

        $scope.chartConfig = {
            options: {
                chart: {
                    type: 'column',
                    zoomType: 'x'
                }
            },
            series: [{
                data: $scope.countItems
            }],
            title: {
                text: 'Campaigns Chart'
            },
            xAxis: {categories: $scope.addNames},
            loading: false
        }
    }).error(function () {
        alert('Error while downloading data!');
    });

});


