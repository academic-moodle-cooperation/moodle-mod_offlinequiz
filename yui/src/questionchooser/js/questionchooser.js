var CSS = {
    ADDNEWQUESTIONBUTTONS: '.menu [data-action="addquestion"]',
    CREATENEWQUESTION: 'div.createnewquestion',
    CHOOSERDIALOGUE: 'div.chooserdialoguebody',
    CHOOSERHEADER: 'div.choosertitle'
};

/**
 * The questionchooser class  is responsible for instantiating and displaying the question chooser
 * when viewing a offlinequiz in editing mode.
 *
 * @class questionchooser
 * @constructor
 * @protected
 * @extends M.core.chooserdialogue
 */
var QUESTIONCHOOSER = function() {
    QUESTIONCHOOSER.superclass.constructor.apply(this, arguments);
};

Y.extend(QUESTIONCHOOSER, M.core.chooserdialogue, {
    initializer: function() {
        Y.one('body').delegate('click', this.display_dialogue, CSS.ADDNEWQUESTIONBUTTONS, this);
    },

    display_dialogue: function(e) {
        e.preventDefault();
        var dialogue = Y.one(CSS.CREATENEWQUESTION + ' ' + CSS.CHOOSERDIALOGUE),
            header = Y.one(CSS.CREATENEWQUESTION + ' ' + CSS.CHOOSERHEADER);

        if (this.container === null) {
            // Setup the dialogue, and then prepare the chooser if it's not already been set up.
            this.setup_chooser_dialogue(dialogue, header, {});
            this.prepare_chooser();
        }

        // Update all of the hidden fields within the questionbank form.
        var parameters = Y.QueryString.parse(e.currentTarget.get('search').substring(1));
        var form = this.container.one('form');
        this.parameters_to_hidden_input(parameters, form, 'returnurl');
        this.parameters_to_hidden_input(parameters, form, 'cmid');
        this.parameters_to_hidden_input(parameters, form, 'category');
        this.parameters_to_hidden_input(parameters, form, 'addonpage');
        this.parameters_to_hidden_input(parameters, form, 'appendqnumstring');

        // Display the chooser dialogue.
        this.display_chooser(e);

        var nodes = Y.all('#chooseform input[type=radio]')._nodes;
        for(i = 0; i < nodes.length; i++) {
            if (nodes[i].id != 'item_qtype_multichoiceset' &&
                nodes[i].id != 'item_qtype_multichoice' &&
                nodes[i].id != 'item_qtype_description' ) {
                nodes[i].disabled = true;
            }
        }
    },

    parameters_to_hidden_input: function(parameters, form, name) {
        var value;
        if (parameters.hasOwnProperty(name)) {
            value = parameters[name];
        } else {
            value = '';
        }
        var input = form.one('input[name=' + name + ']');
        if (!input) {
            input = form.appendChild('<input type="hidden">');
            input.set('name', name);
        }
        input.set('value', value);
    }
}, {
    NAME: 'mod_offlinequiz-questionchooser'
});

M.mod_offlinequiz = M.mod_offlinequiz || {};
M.mod_offlinequiz.init_questionchooser = function() {
    M.mod_offlinequiz.question_chooser = new QUESTIONCHOOSER({});
    return M.mod_offlinequiz.question_chooser;
};
