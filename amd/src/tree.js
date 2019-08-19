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
 * Implement an accessible aria tree widget, from a nested unordered list.
 * Based on http://oaa-accessibility.org/example/41/
 *
 * To respond to selection changed events - use tree.on("selectionchanged", handler).
 * The handler will receive an array of nodes, which are the list items that are currently
 * selected. (Or a single node if multiselect is disabled).
 *
 * @module     tool_lp/tree
 * @package    core
 * @copyright  2019 Ferran Recio Calderó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/url', 'core/log', 'core/ajax'], function($, url, log, ajax) {
    // Private variables and functions.
    /** @var {String} expandedImage The html for an expanded tree node twistie. */
    var expandedImage = $('<img alt="" src="' + url.imageUrl('t/expanded') + '"/>');
    /** @var {String} collapsedImage The html for a collapsed tree node twistie. */
    var collapsedImage = $('<img alt="" src="' + url.imageUrl('t/collapsed') + '"/>');

    /** @var {String} filterInput The html for a tree search and filter input. */
    // var filterInput = $('<input type="text"/>');
    // var starrTogglers = {};
    // var starredDisplay = {};

    /**
     * Constructor
     *
     * @param {String} selector
     */
    var Tree = function(selector) {
        this.elementRoot = $(selector);
        this.treeRoot = this.elementRoot.find('[data-tree=root]');
        if (!this.treeRoot.data('treeinit')) {
            this.items = this.treeRoot.find('li');
            this.expandAll = this.items.length < 20;
            this.parents = this.treeRoot.find('li:has(ul)');

            this.items.attr('aria-selected', 'false');

            this.visibleItems = null;
            this.activeItem = null;
            this.lastActiveItem = null;

            this.filter = this.elementRoot.find('[data-tree=filter]');
            this.starrTogglers = this.elementRoot.find('[data-tree=startoggler]');
            this.starredDisplay = this.elementRoot.find('[data-tree=starred]');
            this.init();

            this.bindEventHandlers();
            this.treeRoot.data('treeinit','true');
        }
    };
    // Public variables and functions.

    /**
     * Init this tree
     * @method init
     */
    Tree.prototype.init = function() {
        this.parents.attr('aria-expanded', 'true');
        this.parents.prepend(expandedImage.clone());

        this.items.attr('role', 'tree-item');
        this.items.attr('tabindex', '-1');
        this.parents.attr('role', 'group');
        this.treeRoot.attr('role', 'tree');

        this.visibleItems = this.treeRoot.find('li');

        //this.filter = filterInput.clone();
        //this.treeRoot.prepend(this.filter);

        var thisObj = this;
        if (!this.expandAll) {
            this.parents.each(function() {
                thisObj.collapseGroup($(this));
            });
            //this.expandGroup(this.parents.first());
        }

        this.starrTogglers.show();
        this.updateStarredElements();
    };

    /**
     * UPdate starred elements list
     * @method updateStarredElements
     */
    Tree.prototype.updateStarredElements = function() {
        var thisObj = this;
        var starredList = thisObj.starredDisplay.find('ul');
        starredList.html('');
        var countStarred = 0;
        this.treeRoot.find('.starred').each(function(index, el) {
            var item = $(el).clone();
            item.find('[data-tree=startoggler]').remove();
            starredList.append(item);
            countStarred++;
        });
        if (countStarred > 0) {
            thisObj.starredDisplay.show();
        } else {
            thisObj.starredDisplay.hide();
        }
    };

    /**
     * Expand a collapsed group.
     *
     * @method expandGroup
     * @param {Object} item is the jquery id of the parent item of the group
     */
    Tree.prototype.expandGroup = function(item) {
        // Find the first child ul node.
        var group = item.children('ul');

        // Expand the group.
        group.show().attr('aria-hidden', 'false');

        item.attr('aria-expanded', 'true');

        item.children('img').attr('src', expandedImage.attr('src'));

        // Update the list of visible items.
        this.visibleItems = this.treeRoot.find('li:visible');
    };

    /**
     * Collapse an expanded group.
     *
     * @method collapseGroup
     * @param {Object} item is the jquery id of the parent item of the group
     */
    Tree.prototype.collapseGroup = function(item) {
        var group = item.children('ul');

        // Collapse the group.
        group.hide().attr('aria-hidden', 'true');

        item.attr('aria-expanded', 'false');

        item.children('img').attr('src', collapsedImage.attr('src'));

        // Update the list of visible items.
        this.visibleItems = this.treeRoot.find('li:visible');
    };

    /**
     * Expand or collapse a group.
     *
     * @method toggleGroup
     * @param {Object} item is the jquery id of the parent item of the group
     */
    Tree.prototype.toggleGroup = function(item) {
        if (item.attr('aria-expanded') == 'true') {
            this.collapseGroup(item);
        } else {
            this.expandGroup(item);
        }
    };

    /**
     * Toggle the selected state for an item back and forth.
     *
     * @method toggleItem
     * @param {Object} item is the jquery id of the item to toggle.
     */
    Tree.prototype.toggleItem = function(item) {
        var current = item.attr('aria-selected');
        if (current === 'true') {
            current = 'false';
        } else {
            current = 'true';
        }
        item.attr('aria-selected', current);
    };

    /**
     * Attach an event listener to the tree.
     *
     * @method on
     * @param {String} eventname This is the name of the event to listen for. Only 'selectionchanged' is supported for now.
     * @param {Function} handler The function to call when the event is triggered.
     */
    Tree.prototype.on = function(eventname, handler) {
        if (eventname !== 'selectionchanged') {
            log.warning('Invalid custom event name for tree. Only "selectionchanged" is supported.');
        } else {
            this.treeRoot.on(eventname, handler);
        }
    };

    /**
     * Handle a double click (expand/collapse).
     *
     * @method handleDblClick
     * @param {Object} item is the jquery id of the parent item of the group
     * @param {Event} e The event.
     * @return {Boolean}
     */
    Tree.prototype.handleDblClick = function(item, e) {
        // Expand or collapse the group.
        this.toggleGroup(item);
        e.stopPropagation();
        return false;
    };

    /**
     * Handle a click (select).
     *
     * @method handleExpandCollapseClick
     * @param {Object} item is the jquery id of the parent item of the group
     * @param {Event} e The event.
     * @return {Boolean}
     */
    Tree.prototype.handleExpandCollapseClick = function(item, e) {
        // Do not shift the focus.
        this.toggleGroup(item);
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
    Tree.prototype.handleFilter = function(e) {
        var thisObj = this;
        var filtertext = this.filter.val();
        if (filtertext == '') {
            this.items.each(function() {
                $(this).show();
            });
            this.parents.each(function() {
                $(this).show();
                thisObj.collapseGroup($(this));
            });
        } else {
            this.items.each(function() {
                if ($(this).text().indexOf(filtertext) != -1 ){
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            this.parents.each(function() {
                thisObj.expandGroup($(this));
            });
        }
        e.stopPropagation();
        return false;
    };

    /**
     * Handle a starred toggler.
     *
     * @method handleExpandCollapseClick
     * @param {Object} item is the jquery id of the parent item of the group
     * @param {Event} e The event.
     * @return {Boolean}
     */
    Tree.prototype.handleStarredClick = function(item, e) {
        var thisObj = this;
        var tablename = item.data('tablename');
        var listItem = item.parents('.dbtable');
        if ( tablename!=undefined) {
            ajax.call([{
                    methodname: 'tool_navdb_starred_table',
                    args: {table: tablename},
                    done: function (response) {
                        if (response.success == true) {
                            listItem.toggleClass('starred');
                            thisObj.updateStarredElements();
                        }
                    },
                    fail: function (ex) {
                        thisObj.lastexception = ex;
                    }
                }]);
        }
        if (e != undefined) {
            e.stopPropagation();
        }
        return false;
    };

    /**
     * Bind the event listeners we require.
     *
     * @method bindEventHandlers
     */
    Tree.prototype.bindEventHandlers = function() {
        var thisObj = this;

        // Bind a dblclick handler to the parent items.
        this.parents.dblclick(function(e) {
            return thisObj.handleDblClick($(this), e);
        });

        // Bind a toggle handler to the expand/collapse icons.
        this.items.children('img').click(function(e) {
            return thisObj.handleExpandCollapseClick($(this).parent(), e);
        });

        // bind filter
        this.filter.keyup(function(e) {
            return thisObj.handleFilter(e);
        });

        // star togglers
        this.starrTogglers.click(function(e) {
            return thisObj.handleStarredClick($(this), e);
        });
    };

    return Tree;
});
