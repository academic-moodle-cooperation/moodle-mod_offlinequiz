/**
 * Resource and activity toolbox class.
 *
 * This class is responsible for managing AJAX interactions with activities and resources
 * when viewing a course in editing mode.
 *
 * @module moodle-course-toolboxes
 * @namespace M.course.toolboxes
 */

// The CSS classes we use.
var CSS = {
    ACTIVITYINSTANCE: 'activityinstance',
    AVAILABILITYINFODIV: 'div.availabilityinfo',
    CONTENTWITHOUTLINK: 'contentwithoutlink',
    CONDITIONALHIDDEN: 'conditionalhidden',
    DIMCLASS: 'dimmed',
    DIMMEDTEXT: 'dimmed_text',
    EDITINSTRUCTIONS: 'editinstructions',
    EDITINGMAXMARK: 'editor_displayed',
    HIDE: 'hide',
    JOIN: 'page_join',
    MODINDENTCOUNT: 'mod-indent-',
    MODINDENTHUGE: 'mod-indent-huge',
    MODULEIDPREFIX: 'slot-',
    PAGE: 'page',
    SECTIONHIDDENCLASS: 'hidden',
    SECTIONIDPREFIX: 'section-',
    SLOT: 'slot',
    SHOW: 'editing_show',
    TITLEEDITOR: 'titleeditor'
},
// The CSS selectors we use.
SELECTOR = {
    ACTIONAREA: '.actions',
    ACTIONLINKTEXT: '.actionlinktext',
    ACTIVITYACTION: 'a.cm-edit-action[data-action], a.editing_maxmark',
    ACTIVITYFORM: 'span.instancemaxmarkcontainer form',
    ACTIVITYICON: 'img.activityicon',
    ACTIVITYINSTANCE: '.' + CSS.ACTIVITYINSTANCE,
    ACTIVITYLINK: '.' + CSS.ACTIVITYINSTANCE + ' > a',
    ACTIVITYLI: 'li.activity',
    ACTIVITYMAXMARK: 'input[name=maxmark]',
    COMMANDSPAN: '.commands',
    CONTENTAFTERLINK: 'div.contentafterlink',
    CONTENTWITHOUTLINK: 'div.contentwithoutlink',
    DESELECTALL: '.deselectall',
    EDITMAXMARK: 'a.editing_maxmark',
    HIDE: 'a.editing_hide',
    HIGHLIGHT: 'a.editing_highlight',
    INSTANCENAME: 'span.instancename',
    INSTANCEMAXMARK: 'span.instancemaxmark',
    MODINDENTDIV: '.mod-indent',
    MODINDENTOUTER: '.mod-indent-outer',
    NUMQUESTIONS: '.numberofquestions',
    PAGECONTENT: 'div#page-content',
    PAGELI: 'li.page',
    SECTIONUL: 'ul.section',
    SELECTMULTIPLECHECKBOX: '.offlinequizbulkcopyform input[type^=checkbox], .select-multiple-checkbox',
    SELECTALL: '.selectall',
    SELECTALLCHECKBOX: '.select-all-checkbox',
    SHOW: 'a.' + CSS.SHOW,
    SHOWHIDE: 'a.editing_showhide',
    SLOTLI: 'li.slot',
    SUMMARKS: '.mod_offlinequiz_summarks'
},
BODY = Y.one(document.body);

// Setup the basic namespace.
M.mod_offlinequiz = M.mod_offlinequiz || {};

/**
 * The toolbox class is a generic class which should never be directly
 * instantiated. Please extend it instead.
 *
 * @class toolbox
 * @constructor
 * @protected
 * @extends Base
 */
var TOOLBOX = function() {
    TOOLBOX.superclass.constructor.apply(this, arguments);
};

Y.extend(TOOLBOX, Y.Base, {
    /**
     * Send a request using the REST API
     *
     * @method send_request
     * @param {Object} data The data to submit with the AJAX request
     * @param {Node} [statusspinner] A statusspinner which may contain a section loader
     * @param {Function} success_callback The callback to use on success
     * @param {Object} [optionalconfig] Any additional configuration to submit
     * @chainable
     */
    send_request: function(data, statusspinner, success_callback, optionalconfig) {
            // Default data structure.
        if (!data) {
            data = {};
        }
        // Handle any variables which we must pass back through to.
        var pageparams = this.get('config').pageparams,
            varname;
        for (varname in pageparams) {
            data[varname] = pageparams[varname];
        }
        data.sesskey = M.cfg.sesskey;
        data.courseid = this.get('courseid');
        data.offlinequizid = this.get('offlinequizid');
        data.offlinegroupid = this.get('offlinegroupid');
        var uri = M.cfg.wwwroot + this.get('ajaxurl');
        // Define the configuration to send with the request.
        var responsetext = [];
        var config = {
            method: 'POST',
            data: data,
            on: {
                success: function(tid, response) {
                    try {
                        responsetext = Y.JSON.parse(response.responseText);
                        if (responsetext.error) {
                            new M.core.ajaxException(responsetext);
                        }
                    } catch (e) {
                    }
                    // Run the callback if we have one.
                    if (responsetext.hasOwnProperty('newsummarks')) {
                        Y.one(SELECTOR.SUMMARKS).setHTML(responsetext.newsummarks);
                    }
                    if (responsetext.hasOwnProperty('newnumquestions')) {
                        Y.one(SELECTOR.NUMQUESTIONS).setHTML(
                                M.util.get_string('numquestionsx', 'offlinequiz', responsetext.newnumquestions));
                    }
                    if (success_callback) {
                        Y.bind(success_callback, this, responsetext)();
                    }
                    if (statusspinner) {
                        window.setTimeout(function() {
                            statusspinner.hide();
                        }, 400);
                    }
                },
                failure: function(tid, response) {
                    if (statusspinner) {
                        statusspinner.hide();
                    }
                    new M.core.ajaxException(response);
                }
            },
            context: this
        };

        // Apply optional config.
        if (optionalconfig) {
            for (varname in optionalconfig) {
                config[varname] = optionalconfig[varname];
            }
        }

        if (statusspinner) {
            statusspinner.show();
        }

        // Send the request.
        Y.io(uri, config);
        return this;
    }
},
{
    NAME: 'mod_offlinequiz-toolbox',
    ATTRS: {
        /**
         * The ID of the Moodle Course being edited.
         *
         * @attribute courseid
         * @default 0
         * @type Number
         */
        courseid: {
            'value': 0
        },

        /**
         * The ID of the offlinequiz being edited.
         *
         * @attribute offlinequizid
         * @default 0
         * @type Number
         */
        offlinequizid: {
            'value': 0
        },
        /**
         * The ID of the offlinequiz group being edited.
         *
         * @attribute offlinequizid
         * @default 0
         * @type Number
         */
        offlinegroupid: {
            'value': 0
        },
        /**
         * The URL to use when submitting requests.
         * @attribute ajaxurl
         * @default null
         * @type String
         */
        ajaxurl: {
            'value': null
        },
        /**
         * Any additional configuration passed when creating the instance.
         *
         * @attribute config
         * @default {}
         * @type Object
         */
        config: {
            'value': {}
        }
    }
}
);
