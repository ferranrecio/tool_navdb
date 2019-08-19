// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Implement a records dinamic table with column deletion buttons
 *
 * @module     tool_lp/rowstable
 * @package    core
 * @copyright  2019 Ferran Recio Calderó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {
    // Private variables and functions.
    /** @var {String} undohide The html for undoing column hide */
    var undohide = $('<span class="badge badge-pill m-1"></span>');

    /**
     * Constructor
     *
     * @param {String} selector
     */
    var Rowstable = function(rootselector) {
        this.elementRoot = $(rootselector);
        if (!this.elementRoot.data('tableinit')) {
            this.deluttons = this.elementRoot.find('[data-delcolumn]');

            this.init();

            this.bindEventHandlers();
            this.elementRoot.data('tableinit','true');
        }
    };
    // Public variables and functions.

    /**
     * Init this tree
     * @method init
     */
    Rowstable.prototype.init = function() {
        // not for now
    };

    /**
     * Handle a filter apply.
     *
     * @method handleClick
     * @param {Object} filter is the jquery id of the filter element
     * @param {Event} e The event.
     * @return {Boolean}
     */
    Rowstable.prototype.handleDeletion = function(item, e) {
        var thisObj = this;
        var column = item.data('delcolumn');
        thisObj.elementRoot.find('.col_'+column).hide();
        // show undo button
        var undobutton = undohide.clone();
        undobutton.html(column);
        undobutton.data('undodelcolumn',column);
        undobutton.click(function(e) {
            return thisObj.handleUndoDeletion($(this), e);
        });
        thisObj.elementRoot.before(undobutton);
        e.stopPropagation();
        return false;
    };

    /**
     * Handle a filter apply.
     *
     * @method handleClick
     * @param {Object} filter is the jquery id of the filter element
     * @param {Event} e The event.
     * @return {Boolean}
     */
    Rowstable.prototype.handleUndoDeletion = function(item, e) {
        var thisObj = this;
        var column = item.data('undodelcolumn');
        thisObj.elementRoot.find('.col_'+column).show();
        // show undo button
        item.remove();
        e.stopPropagation();
        return false;
    };

    /**
     * Bind the event listeners we require.
     *
     * @method bindEventHandlers
     */
    Rowstable.prototype.bindEventHandlers = function() {
        var thisObj = this;

        // Bind a dblclick handler to the parent items.
        this.deluttons.click(function(e) {
            return thisObj.handleDeletion($(this), e);
        });
    };

    return Rowstable;
});
