/**
 * Drag and Drop for offline quiz sections and slots.
 *
 * @module moodle-mod-offlinequiz-dragdrop
 */

// eslint-disable-next-line no-redeclare
var CSS = {
    ACTIONAREA: '.actions',
    ACTIVITY: 'activity',
    ACTIVITYINSTANCE: 'activityinstance',
    CONTENT: 'content',
    COURSECONTENT: 'mod-offlinequiz-edit-content',
    EDITINGMOVE: 'editing_move',
    ICONCLASS: 'iconsmall',
    JUMPMENU: 'jumpmenu',
    LEFT: 'left',
    LIGHTBOX: 'lightbox',
    MOVEDOWN: 'movedown',
    MOVEUP: 'moveup',
    PAGE: 'page',
    PAGECONTENT: 'page-content',
    RIGHT: 'right',
    SECTION: 'section',
    SECTIONADDMENUS: 'section_add_menus',
    SECTIONHANDLE: 'section-handle',
    SLOTS: 'slots',
    SUMMARY: 'summary',
    SECTIONDRAGGABLE: 'sectiondraggable'
},
// The CSS selectors we use.
SELECTOR = {
    PAGE: 'li.page',
    SLOT: 'li.slot'
};
