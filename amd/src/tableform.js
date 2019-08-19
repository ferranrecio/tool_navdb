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
 * @module     tool_lp/tableform
 * @package    core
 * @copyright  2019 Ferran Recio Calderó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/url', 'core/log', 'core/ajax'], function($, url, log, ajax) {
    // Private variables and functions.
    /** @var {String} undohide The html for undoing column hide */
    var filterdview = $('<span class="filterview">...</span>');
    var fieldcheck = {};
    var fieldtransform = {};

    /**
     * Constructor
     *
     * @param {String} selector
     */
    var Tableform = function(rootselector) {
        this.elementRoot = $(rootselector);
        if (!this.elementRoot.data('tableforminit')) {
            this.fields = this.elementRoot.find('.tablefields input');
            this.filter = this.elementRoot.find('[data-formuse=filtervalue]');
            this.operands = this.elementRoot.find('select');
            this.submitbutton = this.elementRoot.find('input[type="submit"]');
            this.init();

            this.bindEventHandlers();
            this.elementRoot.data('tableforminit','true');
        }
    };
    // Public variables and functions.

    /**
     * Init this tree
     * @method init
     */
    Tableform.prototype.init = function() {
        var thisObj = this;
        // add filter show
        this.filterview = filterdview.clone();
        this.filterview.attr('id', 'filterview');
        this.filter.after(this.filterview);
        this.filter.hide();
        // add helpers
        var helperitems = [];
        this.fields.each(function() {
            var item = $(this);
            var helper = item.data('helper');
            if ( helper != undefined && helper != 'undefined') {
                var newview = filterdview.clone();
                newview.attr('id', 'helperview_'+item.attr('name'));
                item.after(newview);
                var newinput = item.clone();
                newinput.attr('id', 'helper_'+item.attr('name'));
                newinput.attr('name', 'helper_'+item.attr('name'));
                newinput.data('helps', item.attr('name'));
                newinput.keyup(function(e) {
                    return thisObj.handleHelperChange($(this),e);
                });
                item.after(newinput);
                helperitems.push(newinput);
                thisObj.handleHelperChange(newinput);
                item.hide();
            }
        });
        this.helpers = helperitems;
    };

    /**
     * return an element SQL clause
     * @method init
     * @return {string} or undefined if it's not valid
     */
    Tableform.prototype.getSQL = function(item) {
        var value = item.val();
        if (value == '') {
            return '';
        }
        var atrname = item.attr('id').replace('fld_','');
        var opitem = $('#op_'+atrname+' option:selected');
        var transform = opitem.data('transform');
        var clause = opitem.data('clause');
        if (transform == undefined) {
            transform = 'none';
        }
        // check if its a valir value
        if (fieldcheck[transform] != undefined) {
            var func = fieldcheck[transform];
            if (!func(value)) {
                return;
            }
            //if (!fieldcheck[transform](value)) return;
        }
        if (fieldtransform[transform] != undefined) {
            //value = fieldtransform[transform](value)) return;
            var func = fieldtransform[transform];
            value = func(value);
        }
        return clause.replace('VAL',value);
    };

    Tableform.prototype.updateFilter = function() {
        var thisObj = this;
        var filtervalues = [];
        var filterror = '';
        thisObj.fields.each(function() {
            var item = $(this);
            var fldsql = thisObj.getSQL(item);
            item.removeClass('inputerror');
            $('#helper_'+item.attr('name')).removeClass('inputerror');
            if (fldsql == undefined) {
                item.addClass('inputerror');
                $('#helper_'+item.attr('name')).addClass('inputerror');
                filterror = 'Invalid value: '+item.attr('id').replace('fld_','');
            }
            if (fldsql != '') {
                filtervalues.push(fldsql);
            }
        });
        // update filter input
        if (filterror != '') {
            thisObj.lastexception = filterror;
            thisObj.filter.val('');
        } else {
            var filtersql = filtervalues.join(' AND ');
            if (filtersql == '') {
                thisObj.filter.val('');
            } else {
                thisObj.filter.val(filtersql);
            }
        }
        this.filterview.html(thisObj.filter.val());
        // disable submit button
        if (thisObj.filter.val() == '') {
            this.submitbutton.attr("disabled", true);
        } else {
            this.submitbutton.removeAttr("disabled");
        }
    };

    Tableform.prototype.updateHelperValue = function(response, helps) {
        var thisObj = this;
        var input = $('#fld_'+helps);
        var helper = $('#helper_'+helps);
        var label = $('#helperview_'+helps);
        label.html(response.human);
        if (response.success == undefined || response.success == false) {
            // item.addClass('inputerror');
            // helper.addClass('inputerror');
            input.val(helper.val());
        } else {
            input.val(response.newvalue);
        }
        thisObj.updateFilter();
    };

    /**
     * Handle a filter apply.
     *
     * @method handleClick
     * @param {Object} filter is the jquery id of the filter element
     * @param {Event} e The event.
     * @return {Boolean}
     */
    Tableform.prototype.handleFieldChange = function(item, e) {
        var thisObj = this;
        thisObj.updateFilter();
        e.stopPropagation();
        return false;
    };

    /**
     * Handle form submit.
     *
     * @method handleClick
     * @param {Object} filter is the jquery id of the filter element
     * @param {Event} e The event.
     * @return {Boolean}
     */
    Tableform.prototype.handleFormSubmit = function(item, e) {
        var thisObj = this;
        if (thisObj.filter.val() == '') {
            e.preventDefault();
            return false;
        }
        return true;
    };

    /**
     * Handle helper change.
     *
     * @method handleClick
     * @param {Object} filter is the jquery id of the filter element
     * @param {Event} e The event.
     * @return {Boolean}
     */
    Tableform.prototype.handleHelperChange = function(item, e) {
        var thisObj = this;
        // call helper
        var value = item.val();
        var helper = item.data('helper');
        var helps = item.data('helps');
        if ( helps!=undefined && helper != undefined) {
            ajax.call([{
                    methodname: 'tool_navdb_get_'+helper,
                    args: {value: value},
                    done: function (response) {
                        thisObj.updateHelperValue(response, helps);
                    },
                    fail: function (ex) {
                        var response = {
                            success: false,
                            newvalue: value,
                            human: 'Error',
                            warnings: [ex],
                        };
                        thisObj.updateHelperValue(response, helps);
                    }
                }]);
        }
        // thisObj.updateFilter();
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
    Tableform.prototype.bindEventHandlers = function() {
        var thisObj = this;

        this.elementRoot.submit(function(e) {
            return thisObj.handleFormSubmit($(this), e);
        });// event.preventDefault()

        this.fields.keyup(function(e) {
            return thisObj.handleFieldChange($(this), e);
        });

        this.operands.change(function(e) {
            return thisObj.handleFieldChange($(this), e);
        });
    };

    // ---- SQL checks ----

    fieldcheck.none = function() {
        return true;
    };

    fieldcheck.addslashes = function() {
        return true;
    };

    fieldcheck.integer = function(value) {
        var ch1 = Number.isInteger(parseInt(value));
        var ch2 = $.isNumeric(value);
        var ch3 = /^[0-9]+$/.test(value);
        return ch1 && ch2 && ch3;
    };

    fieldcheck.numeric = function(value) {
        return $.isNumeric(value);
    };

    fieldcheck.none_array = function() {
        return true;
    };

    fieldcheck.addslashes_array = function() {
        return true;
    };

    fieldcheck.integer_array = function(value) {
        var parts = value.split(',');
        for (var i=0; i < parts.length; i++) {
            if (parts[i] == '') {
                continue;
            }
            if (!fieldcheck.integer(parts[i])) {
                return false;
            }
        }
        return true;
    };

    fieldcheck.numeric_array = function(value) {
        var parts = value.split(',');
        for (var i=0; i < parts.length; i++) {
            if (parts[i] == '') {
                continue;
            }
            if (!fieldcheck.numeric(parts[i])) {
                return false;
            }
        }
        return true;
    };

    // ---- SQL tranform ----

    fieldtransform.none = function(value) {
        return value;
    };

    fieldtransform.addslashes = function(value) {
        var res = value.replace(/'/g, "\\'");
        return "'"+res+"'";
    };

    fieldtransform.integer = function(value) {
        return value;
    };

    fieldtransform.numeric = function(value) {
        var fl = parseFloat(value);
        return fl.toString();
    };

    fieldtransform.none_array = function(value) {
        var parts = value.split(',');
        var res = [];
        for (var i=0; i < parts.length; i++) {
            if (parts[i] == '') {
                continue;
            }
            res.push(fieldtransform.none(parts[i]));
        }
        return res.join(',');
    };

    fieldtransform.addslashes_array = function(value) {
        var parts = value.split(',');
        var res = [];
        for (var i=0; i < parts.length; i++) {
            if (parts[i] == '') {
                continue;
            }
            res.push(fieldtransform.addslashes(parts[i]));
        }
        return res.join(',');
    };

    fieldtransform.integer_array = function(value) {
        var parts = value.split(',');
        var res = [];
        for (var i=0; i < parts.length; i++) {
            if (parts[i] == '') {
                continue;
            }
            res.push(fieldtransform.integer(parts[i]));
        }
        return res.join(',');
    };

    fieldtransform.numeric_array = function(value) {
        var parts = value.split(',');
        var res = [];
        for (var i=0; i < parts.length; i++) {
            if (parts[i] == '') {
                continue;
            }
            res.push(fieldtransform.numeric(parts[i]));
        }
        return res.join(',');
    };

    return Tableform;
});
