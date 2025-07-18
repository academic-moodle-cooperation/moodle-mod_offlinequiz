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
 * Contain the logic for the add random question modal.
 *
 * @module     mod_offlinequiz/modal_add_random_question
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Modal from './add_question_modal';
import * as Notification from 'core/notification';
import * as Fragment from 'core/fragment';
import * as Templates from 'core/templates';
import * as FormChangeChecker from 'core_form/changechecker';
import {call as fetchMany} from 'core/ajax';
import Pending from 'core/pending';

const SELECTORS = {
    ANCHOR: 'a[href]',
    EXISTING_CATEGORY_CONTAINER: '[data-region="existing-category-container"]',
    EXISTING_CATEGORY_TAB: '#id_existingcategoryheader',
    NEW_CATEGORY_CONTAINER: '[data-region="new-category-container"]',
    NEW_CATEGORY_TAB: '#id_newcategoryheader',
    TAB_CONTENT: '[data-region="tab-content"]',
    ADD_ON_PAGE_FORM_ELEMENT: '[name="addonpage"]',
    ADD_RANDOM_BUTTON: 'input[type="submit"][name="addrandom"]',
    ADD_NEW_CATEGORY_BUTTON: 'input[type="submit"][name="newcategory"]',
    SUBMIT_BUTTON_ELEMENT: 'input[type="submit"][name="addrandom"], input[type="submit"][name="newcategory"]',
    FORM_HEADER: 'legend',
    SELECT_NUMBER_TO_ADD: '#menurandomcount',
    NEW_CATEGORY_ELEMENT: '#categoryname',
    PARENT_CATEGORY_ELEMENT: '#parentcategory',
    FILTER_CONDITION_ELEMENT: '[data-filtercondition]',
    FORM_ELEMENT: '#add_random_question_form',
    MESSAGE_INPUT: '[name="message"]',
    SWITCH_TO_OTHER_BANK: 'button[data-action="switch-question-bank"]',
    NEW_BANKMOD_ID: 'data-newmodid',
    BANK_SEARCH: '#searchbanks',
    GO_BACK_BUTTON: 'button[data-action="go-back"]',
    GROUP_ID: 'select[name="groupnumber"]',
};

export default class ModalAddRandomQuestion extends Modal {
    static TYPE = 'mod_offlinequiz-offlinequiz-add-random-question';
    static TEMPLATE = 'mod_offlinequiz/modal_add_random_question';

    /**
     * Create the add random question modal.
     *
     * @param  {Number} contextId Current context id.
     * @param  {Number} bankCmId Current question bank course module id.
     * @param  {string} category Category id and category context id comma separated.
     * @param  {string} returnUrl URL to return to after form submission.
     * @param  {Number} quizCmId Current quiz course module id.
     * @param  {boolean} showNewCategory Display the New category tab when selecting random questions.
     */
    static init(
        contextId,
        bankCmId,
        category,
        returnUrl,
        quizCmId,
        showNewCategory = true
    ) {
        const selector = '.menu [data-action="addarandomquestion"]';
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest(selector);
            if (!trigger) {
                return;
            }
            e.preventDefault();

            ModalAddRandomQuestion.create({
                contextId,
                bankCmId,
                category,
                returnUrl,
                quizCmId,
                showNewCategory,
                title: trigger.dataset.header,
                addOnPage: trigger.dataset.addonpage,

                templateContext: {
                    hidden: showNewCategory,
                },
            });
        });
    }

    /**
     * Constructor for the Modal.
     *
     * @param {object} root The root jQuery element for the modal
     */
    constructor(root) {
        super(root);
        this.category = null;
        this.returnUrl = null;
        this.quizCmId = null;
        this.loadedForm = false;
    }

    configure(modalConfig) {
        modalConfig.removeOnClose = true;

        this.setCategory(modalConfig.category);
        this.setReturnUrl(modalConfig.returnUrl);
        this.showNewCategory = modalConfig.showNewCategory;

        super.configure(modalConfig);
    }

    /**
     * Set the id of the page that the question should be added to
     * when the user clicks the add to quiz link.
     *
     * @method setAddOnPageId
     * @param {int} id
     */
    setAddOnPageId(id) {
        super.setAddOnPageId(id);
        this.getBody().find(SELECTORS.ADD_ON_PAGE_FORM_ELEMENT).val(id);
    }

    /**
     * Set the category for this form. The category is a comma separated
     * category id and category context id.
     *
     * @method setCategory
     * @param {string} category
     */
    setCategory(category) {
        this.category = category;
    }

    /**
     * Returns the saved category.
     *
     * @method getCategory
     * @return {string}
     */
    getCategory() {
        return this.category;
    }

    /**
     * Set the return URL for the form.
     *
     * @method setReturnUrl
     * @param {string} url
     */
    setReturnUrl(url) {
        this.returnUrl = url;
    }

    /**
     * Returns the return URL for the form.
     *
     * @method getReturnUrl
     * @return {string}
     */
    getReturnUrl() {
        return this.returnUrl;
    }

    /**
     * Moves a given form element inside (a child of) a given tab element.
     *
     * Hides the 'legend' (e.g. header) element of the form element because the
     * tab has the name.
     *
     * Moves the submit button into a footer element at the bottom of the form
     * element for styling purposes.
     *
     * @method moveContentIntoTab
     * @param  {jquery} tabContent The form element to move into the tab.
     * @param  {jquey} tabElement The tab element for the form element to move into.
     */
    moveContentIntoTab(tabContent, tabElement) {
        // Hide the header because the tabs show us which part of the form we're
        // looking at.
        tabContent.find(SELECTORS.FORM_HEADER).addClass('hidden');
        // Move the element inside a tab.
        tabContent.wrap(tabElement);
    }

    /**
     * Empty the tab content container and move all tabs from the form into the
     * tab container element.
     *
     * @method moveTabsIntoTabContent
     * @param  {jquery} form The form element.
     */
    moveTabsIntoTabContent(form) {
        // Empty it to remove the loading icon.
        const tabContent = this.getBody().find(SELECTORS.TAB_CONTENT).empty();
        // Make sure all tabs are inside the tab content element.
        form.find('[role="tabpanel"]').wrapAll(tabContent);
    }

    /**
     * Make sure all of the tabs have a cancel button in their fotter to sit along
     * side the submit button.
     *
     * @method moveCancelButtonToTabs
     * @param  {jquey} form The form element.
     */
    moveCancelButtonToTabs(form) {
        const cancelButton = form.find(SELECTORS.CANCEL_BUTTON_ELEMENT).addClass('ms-1');
        const tabFooters = form.find('[data-region="footer"]');
        // Remove the buttons container element.
        cancelButton.closest(SELECTORS.BUTTON_CONTAINER).remove();
        cancelButton.clone().appendTo(tabFooters);
    }

    /**
     * Load the add random question form in a fragement and perform some transformation
     * on the HTML to convert it into tabs for rendering in the modal.
     *
     * @method loadForm
     * @return {promise} Resolved with form HTML and JS.
     */
    loadForm() {
        const addonpage = this.getAddOnPageId();
        const returnurl = this.getReturnUrl();
        const quizcmid = this.quizCmId;
        const bankcmid = this.bankCmId;

        return Fragment.loadFragment(
            'mod_offlinequiz',
            'add_random_question_form',
            this.getContextId(),
            {
                addonpage,
                returnurl,
                quizcmid,
                bankcmid,
            }
        )
            .then((html, js) => {
                const form = $(html);
                const existingCategoryTabContent = form.find(SELECTORS.EXISTING_CATEGORY_TAB);
                const existingCategoryTab = this.getBody().find(SELECTORS.EXISTING_CATEGORY_CONTAINER);
                const newCategoryTabContent = form.find(SELECTORS.NEW_CATEGORY_TAB);
                const newCategoryTab = this.getBody().find(SELECTORS.NEW_CATEGORY_CONTAINER);

                // Transform the form into tabs for better rendering in the modal.
                this.moveContentIntoTab(existingCategoryTabContent, existingCategoryTab);
                this.moveContentIntoTab(newCategoryTabContent, newCategoryTab);
                this.moveTabsIntoTabContent(form);

                Templates.replaceNode(this.getBody().find(SELECTORS.TAB_CONTENT), form, js);
                return;
            })
            .then(() => {
                // Make sure the form change checker is disabled otherwise it'll stop the user from navigating away from the
                // page once the modal is hidden.
                FormChangeChecker.disableAllChecks();

                // Add question to quiz.
                this.getBody()[0].addEventListener('click', (e) => {
                    const button = e.target.closest(SELECTORS.SUBMIT_BUTTON_ELEMENT);
                    if (!button) {
                        return;
                    }
                    e.preventDefault();

                    // Intercept the submission to adjust the POST params so that the quiz mod id is set and not the bank module id.
                    document.querySelector('#questionscontainer input[name="cmid"]').setAttribute('name', this.quizCmId);

                    // Add Random questions if the add random button was clicked.
                    const addRandomButton = e.target.closest(SELECTORS.ADD_RANDOM_BUTTON);
                    if (addRandomButton) {
                        const randomcount = document.querySelector(SELECTORS.SELECT_NUMBER_TO_ADD).value;
                        const filtercondition = document.querySelector(SELECTORS.FILTER_CONDITION_ELEMENT).dataset?.filtercondition;
                        const groupid = document.querySelector(SELECTORS.GROUP_ID).value;

                        this.addQuestions(quizcmid, addonpage, randomcount, filtercondition, '', '', groupid);
                        return;
                    }
                    // Add new category if the add category button was clicked.
                    const addCategoryButton = e.target.closest(SELECTORS.ADD_NEW_CATEGORY_BUTTON);
                    if (addCategoryButton) {
                        this.addQuestions(
                            quizcmid,
                            addonpage,
                            1,
                            '',
                            document.querySelector(SELECTORS.NEW_CATEGORY_ELEMENT).value,
                            document.querySelector(SELECTORS.PARENT_CATEGORY_ELEMENT).value
                        );
                        return;
                    }
                });

                this.getModal().on('click', SELECTORS.SWITCH_TO_OTHER_BANK, () => {
                    this.handleSwitchBankContentReload(SELECTORS.BANK_SEARCH)
                        .then(function(ModalOfflinequizQuestionBank) {
                            $(SELECTORS.BANK_SEARCH)?.on('change', (e) => {
                                const bankCmId = $(e.currentTarget).val();
                                // Have to recreate the modal as we have already used the body for the switch bank content.
                                if (bankCmId > 0) {
                                    ModalAddRandomQuestion.create({
                                        'contextId': ModalOfflinequizQuestionBank.getContextId(),
                                        'bankCmId': bankCmId,
                                        'category': ModalOfflinequizQuestionBank.getCategory(),
                                        'returnUrl': ModalOfflinequizQuestionBank.getReturnUrl(),
                                        'quizCmId': ModalOfflinequizQuestionBank.quizCmId,
                                        'title': ModalOfflinequizQuestionBank.originalTitle,
                                        'addOnPage': ModalOfflinequizQuestionBank.getAddOnPageId(),
                                        'templateContext': {hidden: ModalOfflinequizQuestionBank.showNewCategory},
                                        'showNewCategory': ModalOfflinequizQuestionBank.showNewCategory,
                                    })
                                        .then(ModalOfflinequizQuestionBank.destroy())
                                        .catch(Notification.exception);
                                }
                            });
                            return ModalOfflinequizQuestionBank;
                        });
                });

                this.getModal().on('click', SELECTORS.GO_BACK_BUTTON, (e) => {
                    const anchorElement = $(e.currentTarget);
                    // Have to recreate the modal as we have already used the body for the switch bank content.
                    ModalAddRandomQuestion.create({
                        'contextId': this.getContextId(),
                        'bankCmId': anchorElement.attr('value'),
                        'category': this.getCategory(),
                        'returnUrl': this.getReturnUrl(),
                        'quizCmId': this.quizCmId,
                        'title': this.originalTitle,
                        'addOnPage': this.getAddOnPageId(),
                        'templateContext': {hidden: this.showNewCategory},
                        'showNewCategory': this.showNewCategory,
                    }).then(this.destroy()).catch(Notification.exception);
                });

                this.getModal().on('click', SELECTORS.ANCHOR, (e) => {
                    const anchorElement = $(e.currentTarget);
                    // Have to recreate the modal as we have already used the body for the switch bank content.
                    if (anchorElement.closest('a[' + SELECTORS.NEW_BANKMOD_ID + ']').length) {
                        ModalAddRandomQuestion.create({
                            'contextId': this.getContextId(),
                            'bankCmId': anchorElement.attr(SELECTORS.NEW_BANKMOD_ID),
                            'category': this.getCategory(),
                            'returnUrl': this.getReturnUrl(),
                            'quizCmId': this.quizCmId,
                            'title': this.originalTitle,
                            'addOnPage': this.getAddOnPageId(),
                            'templateContext': {hidden: this.showNewCategory},
                            'showNewCategory': this.showNewCategory,
                        }).then(this.destroy()).catch(Notification.exception);
                    }
                });
            })
            .catch(Notification.exception);
    }

    /**
     * Call web service function to add random questions
     *
     * @param {number} quizcmid the course module id of the quiz to add questions to.
     * @param {number} addonpage the page where random questions will be added to
     * @param {number} randomcount Number of random questions
     * @param {string} filtercondition Filter condition
     * @param {string} newcategory add new category
     * @param {string} parentcategory parent category of new category
     * @param {number} groupid group id
     */
    async addQuestions(
        quizcmid,
        addonpage,
        randomcount,
        filtercondition,
        newcategory,
        parentcategory,
        groupid
    ) {
        // We do not need to resolve this Pending because the form submission will result in a page redirect.
        new Pending('mod-offlinequiz/modal_add_random_questions');
        const call = {
            methodname: 'mod_offlinequiz_add_random_questions',
            args: {
                cmid: quizcmid,
                addonpage,
                randomcount,
                filtercondition,
                newcategory,
                parentcategory,
                groupid,
            }
        };
        try {
            const response = await fetchMany([call])[0];
            const form = document.querySelector(SELECTORS.FORM_ELEMENT);
            const messageInput = form.querySelector(SELECTORS.MESSAGE_INPUT);
            messageInput.value = response.message;
            form.submit();
        } catch (e) {
            Notification.exception(e);
        }
    }

    /**
     * Override the modal show function to load the form when this modal is first
     * shown.
     *
     * @method show
     */
    show() {
        super.show(this);

        if (!this.loadedForm) {
            this.tabHtml = this.getBody();
            this.loadForm(window.location.search);
            this.loadedForm = true;
        }
    }
}

ModalAddRandomQuestion.registerModalType();
