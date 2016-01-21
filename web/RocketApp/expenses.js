/* global angular */

/**
 * Expenses page.
 *
 * @type {angular.Module}
 */
var app = angular.module('expenses', ['ngResource']);
app.controller('ExpensesController', ['$scope', '$resource',
    function($scope, $resource) {
        $scope.name = 'ExpensesController';
    }
]);
