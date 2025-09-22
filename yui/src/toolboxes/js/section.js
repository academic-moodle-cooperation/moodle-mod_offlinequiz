/**
 * Resource and activity toolbox class.
 *
 * This class is responsible for managing AJAX interactions with activities and resources
 * when viewing a course in editing mode.
 *
 * @module moodle-mod_offlinequiz-toolboxes
 * @namespace M.mod_offlinequiz.toolboxes
 */

/**
 * Section toolbox class.
 *
 * This class is responsible for managing AJAX interactions with sections
 * when viewing a course in editing mode.
 *
 * @class section
 * @constructor
 * @extends M.mod_offlinequiz.toolboxes.toolbox
 */
var SECTIONTOOLBOX = function() {
    SECTIONTOOLBOX.superclass.constructor.apply(this, arguments);
};

Y.extend(SECTIONTOOLBOX, TOOLBOX, {
    /**
     * An Array of events added when editing a max mark field.
     * These should all be detached when editing is complete.
     *
     * @property editsectionevents
     * @protected
     * @type Array
     * @protected
     */
    editsectionevents: [],

    /**
     * Initialize the section toolboxes module.
     *
     * Updates all span.commands with relevant handlers and other required changes.
     *
     * @method initializer
     * @protected
     */
    initializer: function() {
        M.mod_offlinequiz.offlinequizbase.register_module(this);

        BODY.delegate('key', this.handle_data_action, 'down:enter', SELECTOR.ACTIVITYACTION, this);
        Y.delegate('click', this.handle_data_action, BODY, SELECTOR.ACTIVITYACTION, this);
        Y.delegate('change', this.handle_data_action, BODY, SELECTOR.EDITSHUFFLEQUESTIONSACTION, this);
    },

    // eslint-disable-next-line camelcase
    toggle_hide_section: function(e) {
        // Prevent the default button action.
        e.preventDefault();

        // Get the section we're working on.
        var section = e.target.ancestor(M.mod_offlinequiz.format.get_section_selector(Y)),
            button = e.target.ancestor('a', true),
            hideicon = button.one('img'),

        // The value to submit.
            value,

        // The text for strings and images. Also determines the icon to display.
            action,
            nextaction;

        if (!section.hasClass(CSS.SECTIONHIDDENCLASS)) {
            section.addClass(CSS.SECTIONHIDDENCLASS);
            value = 0;
            action = 'hide';
            nextaction = 'show';
        } else {
            section.removeClass(CSS.SECTIONHIDDENCLASS);
            value = 1;
            action = 'show';
            nextaction = 'hide';
        }

        var newstring = M.util.get_string(nextaction + 'fromothers', 'format_' + this.get('format'));
        hideicon.setAttrs({
            'alt': newstring,
            'src': M.util.image_url('i/' + nextaction)
        });
        button.set('title', newstring);

        // Change the highlight status.
        var data = {
            'class': 'section',
            'field': 'visible',
            'id': Y.Moodle.core_course.util.section.getId(
                        section.ancestor(M.mod_offlinequiz.edit.get_section_wrapper(Y), true)),
            'value': value
        };
        var lightbox = M.util.add_lightbox(Y, section);
        lightbox.show();

        this.send_request(data, lightbox, function(response) {
            var activities = section.all(SELECTOR.ACTIVITYLI);
            activities.each(function(node) {
                var button;
                if (node.one(SELECTOR.SHOW)) {
                    button = node.one(SELECTOR.SHOW);
                } else {
                    button = node.one(SELECTOR.HIDE);
                }
                var activityid = Y.Moodle.mod_offlinequiz.util.slot.getId(node);

                // NOTE: resourcestotoggle is returned as a string instead
                // of a Number so we must cast our activityid to a String.
                if (Y.Array.indexOf(response.resourcestotoggle, "" + activityid) !== -1) {
                    M.mod_offlinequiz.resource_toolbox.handle_resource_dim(button, node, action);
                }
            }, this);
        });
    }
}, {
    NAME: 'mod_offlinequiz-section-toolbox',
    ATTRS: {
        courseid: {
            'value': 0
        },
        offlinequizid: {
            'value': 0
        },
        offlinegroupid: {
            'value': 0
        },
        format: {
            'value': 'topics'
        }
    }
});

// eslint-disable-next-line camelcase
M.mod_offlinequiz.init_section_toolbox = function(config) {
    return new SECTIONTOOLBOX(config);
};
