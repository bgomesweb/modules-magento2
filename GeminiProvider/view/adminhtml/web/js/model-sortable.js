define([
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    /**
     * Enables drag-to-reorder on a dynamic-row config grid's tbody, keeping it in sync
     * with rows added after init via the grid's own prototypeJS "add" handler.
     *
     * @param {String} rowsContainerId - id of the <tbody> holding the rows
     * @param {String} arrayRowGlobalName - name of the window.arrayRow{htmlId} object
     */
    return function (rowsContainerId, arrayRowGlobalName) {
        var $rows = $('#' + rowsContainerId),
            arrayRow = window[arrayRowGlobalName];

        if (arrayRow && typeof arrayRow.add === 'function') {
            var originalAdd = arrayRow.add;

            arrayRow.add = function () {
                originalAdd.apply(arrayRow, arguments);
                $rows.sortable('refresh');
            };
        }

        $rows.sortable({
            axis: 'y',
            handle: '[data-role="draggable-handle"]',
            items: '> tr',
            tolerance: 'pointer'
        });
    };
});
