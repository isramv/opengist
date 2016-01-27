/* global angular */

/**
 * Expenses page.
 *
 * @type {angular.Module}
 */
var app = angular.module('expenses', ['ngResource']);
// Factory
app.factory('Expenses', ['$resource', function($resource) {
        return $resource('api/v1/expenses', {}, {
            'get': {
                method: 'GET',
                isArray: true
            }
        });
    }
]);

app.controller('ExpensesController', ['$scope', 'Expenses',
    function($scope, Expenses) {
        $scope.name = 'ExpensesController';
        function load_data() {
            $scope.expenses = Expenses.get().$promise.then(function(data) {
                $scope.expenses = data;
            });
        }
        $scope.load_expenses = function() {
            load_data();
        }
    }
]);
