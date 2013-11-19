<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_StaffAction extends Gems_Controller_BrowseEditAction
{
    protected $_instanceId;
    protected $_organizations;

    /**
     * The current user for detailed actions, set by createModel()
     *
     * @var Gems_User_User
     */
    protected $_user = false;

    //@@TODO What if we want a different one per organization?
    //Maybe check if org has a default and otherwise use this one?
    public $defaultStaffDefinition = Gems_User_UserLoader::USER_STAFF;

    public $filterStandard = array('gsf_active' => 1);

    public $menu;
    /**
     *
     * @var Gems_Loader
     */
    public $loader;

    /**
     *
     * @var Gems_Project_ProjectSettings
     */
    public $project;

    public $request;

    public $sortKey = array('name' => SORT_ASC);

    public $view;

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Adds a button column to the model, if such a button exists in the model.
     *
     * @param MUtil_Model_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @rturn void
     */
    protected function addBrowseTableColumns(MUtil_Model_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        // Add edit button if allowed, otherwise show, again if allowed
        if ($menuItem = $this->findAllowedMenuItem('show')) {
            $bridge->addItemLink($menuItem->toActionLinkLower($this->getRequest(), $bridge));
        }

        $br = MUtil_Html::create('br');
        $orgCount = count($model->get('gsf_id_organization', 'multiOptions'));
        foreach($model->getItemsOrdered() as $name) {
            if ($label = $model->get($name, 'label')) {
                switch ($name) {
                    case 'name':
                        if ($orgCount > 1) {
                            $bridge->addMultiSort('name', $br, 'gsf_email');
                        } else {
                            $bridge->addSortable($name, $label);
                        }

                        break;

                    case 'gsf_email':
                        if ($orgCount > 1) {
                            //Do nothing as it is already linked in the 'name' field
                        } else {
                            $bridge->addSortable($name, $label);
                        }
                        break;

                    case 'gsf_id_organization':
                        if ($orgCount > 1) {
                            $bridge->addSortable($name, $label);
                        } else {
                            //Don't show as it is always the same
                        }
                        break;

                    default:
                        $bridge->addSortable($name, $label);
                        break;
                }
            }
        }
        // Add edit button if allowed
        if ($menuItem = $this->findAllowedMenuItem('edit')) {
            $bridge->addItemLink($menuItem->toActionLinkLower($this->getRequest(), $bridge));
        }
        // Add reset button if allowed
        if ($menuItem = $this->findAllowedMenuItem('reset')) {
            $bridge->addItemLink($menuItem->toActionLink($this->getRequest(), $bridge, $this->_('password')));
        }
        if ($menuItem = $this->findAllowedMenuItem('mail')) {
            $bridge->addItemLink($menuItem->toActionLink($this->getRequest(), $bridge));
        }
    }

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @param array $data The data that will later be loaded into the form
     * @param optional boolean $new Form should be for a new element
     * @return void|array When an array of new values is return, these are used to update the $data array in the calling function
     */
    protected function addFormElements(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model, array $data, $new = false)
    {
        // Sorry, for the time being no password complexity checking on new
        // users. Can be done, but is to complex for the moment.

        // Find out if this group is in the inheritance path of the current user
        // and allow those certain groups
        $model->set('gsf_id_primary_group', 'multiOptions', $this->util->getDbLookup()->getAllowedStaffGroups());

        if ($new) {
            $model->set('gsf_id_primary_group', 'default', $this->util->getDbLookup()->getDefaultGroup());
        }

        $ucfirst = new Zend_Filter_Callback('ucfirst');

        $bridge->addHiddenMulti('gsf_id_user', 'gul_id_user', 'gup_id_user', 'gul_login', 'gul_id_organization');

        //Escape for local users when using radius, should be changed to something more elegant later
        //@@TODO: Think of a better way to allow multiple methods per organization
        if ($this->escort->hasPrivilege('pr.staff.edit.all')) {
            $model->set('gul_user_class', 'label', $this->_('User Definition'));

            //Make sure old or experimental userdefinitions don't have to be changed to something that is
            //allowed at the moment. For example the oldStaffUser can stay when editing a user.
            $options = $model->get('gul_user_class', 'multiOptions');
            if (! array_key_exists($data['gul_user_class'], $options)) {
                $options[$data['gul_user_class']] = $this->_('Unsupported User Definition');
                $model->set('gul_user_class', 'multiOptions', $options);
            }
            $bridge->add('gul_user_class');
        } else {
            $bridge->addHidden('gul_user_class');
        }
        //@@TODO: How do we change this? Only per org, or allow per user?
        //What classes are available? Maybe use something like event loader and add a little desc. to each type?
        $bridge->addText('gsf_login', 'size', 15, 'minlength', 4,
            'validator', $model->createUniqueValidator(array('gsf_login', 'gsf_id_organization'), array('gsf_id_user')));

        // Can the organization be changed?
        if ($this->escort->hasPrivilege('pr.staff.edit.all')) {
            // $bridge->addHiddenMulti($model->getKeyCopyName('gsf_id_organization'));
            $bridge->addSelect('gsf_id_organization');
        } else {
            $bridge->addExhibitor('gsf_id_organization');
        }

        $bridge->addRadio(   'gsf_gender',         'separator', '');
        $bridge->addText(    'gsf_first_name',     'label', $this->_('First name'));
        $bridge->addFilter(  'gsf_first_name',     $ucfirst);
        $bridge->addText(    'gsf_surname_prefix', 'label', $this->_('Surname prefix'), 'description', 'de, van der, \'t, etc...');
        $bridge->addText(    'gsf_last_name',      'label', $this->_('Last name'), 'required', true);
        $bridge->addFilter(  'gsf_last_name',      $ucfirst);
        $bridge->addText(    'gsf_email', array('size' => 30))->addValidator('SimpleEmail');

        $bridge->add('gsf_id_primary_group');
        $bridge->addCheckbox('gul_can_login', 'description', $this->_('Users can only login when this box is checked.'));
        $bridge->addCheckbox('gsf_logout_on_survey', 'description', $this->_('If checked the user will logoff when answering a survey.'));

        $bridge->addSelect('gsf_iso_lang');
    }

    public function afterFormLoad(array &$data, $isNew)
    {
        if (array_key_exists('glf_login', $data)) {
            $this->_instanceId = $data['gsf_login'];
        }

        if (!isset($data['gsf_id_organization']) || empty($data['gsf_id_organization'])) {
            $data['gsf_id_organization'] = $this->menu->getParameterSource()->getMenuParameter('gsf_id_organization', $this->loader->getCurrentUser()->getCurrentOrganizationId());
        }

        if (! ($this->escort->hasPrivilege('pr.staff.edit.all') ||
               array_key_exists($data['gsf_id_organization'], $this->loader->getCurrentUser()->getAllowedOrganizations()))) {
                throw new Zend_Exception($this->_('You are not allowed to edit this staff member.'));
        }
    }

    /**
     * Modified createAction to allow reactivation of deleted users
     *
     * When the gsf_login had the 'recordFound' error, we check if the user is deactivated.
     * If so present the user and ask to confirm reactivation, otherwise show the erroneous form.
     *
     * When activated, we reroute to the edit action after reactivation (gsf_active => 1 and gul_can_login => 1)
     *
     * Uses $this->getModel()
     *      $this->addFormElements()
     */
    public function createAction()
    {
        $this->html->h3(sprintf($this->_('New %s...'), $this->getTopic()));

        $confirmed = $this->getRequest()->getParam('confirmed');
        $id        = $this->getRequest()->getParam('id');
        if (!is_null($confirmed)) {
            if ($confirmed == true && intval($id) > 0) {
                $id    = intval($id);
                $model = $this->getModel();
                $model->setFilter(array('gsf_id_user' => $id));
                $result       = $model->loadFirst();
                if ($result) {
                    if ($result['gsf_active'] == 0 || $result['gul_can_login'] == 0) {
                        $result['gsf_active']    = 1;
                        $result['gul_can_login'] = 1;
                        $model->save($result);
                        $this->_reroute(array('action' => 'edit', 'id'     => $id), true);
                    }
                }
            }
            //Not confirmed or id invalid redirect to index
            $this->_reroute(array('action' => 'index'), true);
        }

        if ($form = $this->processForm()) {
            if ($element = $form->getElement('gsf_login')) {
                $errors = $element->getErrors();
                if (array_search('recordFound', $errors) !== false) {
                    //We have a duplicate login!
                    $model = $this->getModel();
                    $model->setFilter(array(
                        'gsf_login'           => $form->getValue('gsf_login'),
                        'gsf_id_organization' => $form->getValue('gsf_id_organization')
                    ));
                    $result = $model->load();

                    if (count($result) == 1) {
                        $result = array_shift($result); //Get the first (only) row
                        if ($result['gsf_active'] == 0 || $result['gul_can_login'] == 0) {
                            //Ok we try to add an inactive user...
                            //now ask if this is the one we would like to reactivate?
                            $question = sprintf($this->_('User with id %s already exists but is deleted, do you want to reactivate the account?'), $result['gsf_login']);

                            $repeater = $model->loadRepeatable();
                            $table    = $this->getShowTable();
                            $table->caption($question);
                            $table->setRepeater($repeater);

                            $footer = $table->tfrow($question, ' ', array('class' => 'centerAlign'));
                            $footer->actionLink(array('confirmed' => true, 'id' => $result['gsf_id_user']), $this->_('Yes'));
                            $footer->actionLink(array('confirmed' => 0), $this->_('No'));

                            $this->html[] = $table;
                            $this->html->buttonDiv($this->createMenuLinks());
                            return;
                        } else {
                            //User is active... this is a real duplicate so continue the flow
                        }
                    }
                }
            }
            $this->html[] = $form;
        }
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        if ($detailed) {
            // Make sure the user is loaded
            $this->loadUser();

            if ($this->_user) {
                switch ($action) {
                    case 'create':
                    case 'show':
                        break;

                    default:
                        if (! $this->_user->hasAllowedRole()) {
                            throw new Gems_Exception($this->_('No access to page'), 403, null,
                                sprintf($this->_('Access to this page is not allowed for current role: %s.'), $this->loader->getCurrentUser()->getRole()));
                        }
                }
            }
        }

        // MUtil_Model::$verbose = true;
        $model = $this->loader->getModels()->getStaffModel();

        $model->set('gsf_login',            'label', $this->_('Username'));
        $model->set('name',                 'label', $this->_('Name'),
            'column_expression', "CONCAT(COALESCE(CONCAT(gsf_last_name, ', '), '-, '), COALESCE(CONCAT(gsf_first_name, ' '), ''), COALESCE(gsf_surname_prefix, ''))");
        $model->set('gsf_email',            'label', $this->_('E-Mail'), 'itemDisplay', 'MUtil_Html_AElement::ifmail');

        $availableOrganizations = $this->util->getDbLookup()->getOrganizations();
        if ($this->escort->hasPrivilege('pr.staff.see.all')) {
            // Select organization
            $options = array('' => $this->_('(all organizations)')) + $availableOrganizations;
        } else {
            $allowedOrganizations   = $this->loader->getCurrentUser()->getAllowedOrganizations();
            $options = array_intersect($availableOrganizations, $allowedOrganizations);
        }
        $model->set('gsf_id_organization',  'label', $this->_('Organization'), 'multiOptions', $options);

        $model->set('gsf_id_primary_group', 'label', $this->_('Primary function'), 'multiOptions', MUtil_Lazy::call($this->util->getDbLookup()->getStaffGroups));
        $model->set('gsf_gender',           'label', $this->_('Gender'), 'multiOptions', $this->util->getTranslated()->getGenders());

        if ($detailed) {
            //Now try to load the current organization and find out if it has a default user definition
            //otherwise use the defaultStaffDefinition
            $org    = $this->loader->getOrganization($this->menu->getParameterSource()->getMenuParameter('gsf_id_organization', $this->loader->getCurrentUser()->getCurrentOrganizationId()));
            $orgDef = $org->get('gor_user_class', $this->defaultStaffDefinition);
            $model->set('gul_user_class',       'default', $orgDef, 'multiOptions', $this->loader->getUserLoader()->getAvailableStaffDefinitions());
            $model->set('gsf_iso_lang',
                    'label', $this->_('Language'),
                    'multiOptions', $this->util->getLocalized()->getLanguages(),
                    'default', $this->project->locale['default']
                    );
            $model->set('gul_can_login',        'label', $this->_('Can login'), 'multiOptions', $this->util->getTranslated()->getYesNo(), 'default', 1);
            $model->set('gsf_logout_on_survey', 'label', $this->_('Logout on survey'), 'multiOptions', $this->util->getTranslated()->getYesNo());
        }

        $model->setDeleteValues('gsf_active', 0, 'gul_can_login', 0);

        return $model;
    }

    /**
     * Return an array with route options depending on de $data given.
     *
     * @param mixed $data array or Zend_Controller_Request_Abstract
     * @return mixed array with route options or false when no redirect is found
     */
    public function getAfterSaveRoute($data)
    {
        if (! $this->_user) {
            $this->_user = $this->loader->getUser($data['gul_login'], $data['gul_id_organization']);
        }
        //MUtil_Echo::track($this->_user->canSetPassword());

        if ($this->_user->canSetPassword()) {
            if ($currentItem = $this->menu->getCurrent()) {
                $controller = $this->_getParam('controller');

                if ($data instanceof Zend_Controller_Request_Abstract) {
                    $refData = $data;
                    $refData->setParam('accessible_role', $this->_user->hasAllowedRole());
                } elseif (is_array($data)) {
                    $refData = $this->getModel()->getKeyRef($data) + $data;
                    $refData['accessible_role'] = $this->_user->hasAllowedRole();
                } else {
                    throw new Gems_Exception_Coding('The variable $data must be an array or a Zend_Controller_Request_Abstract object.');
                }

                // Look for reset
                if ($menuItem = $this->menu->findController($controller, 'reset')) {
                    return $menuItem->toRouteUrl($refData);
                }
            }
        }

        return parent::getAfterSaveRoute($data);
    }

    protected function getAutoSearchElements(MUtil_Model_ModelAbstract $model, array $data)
    {
        $elements = parent::getAutoSearchElements($model, $data);

        $availableOrganizations = $this->util->getDbLookup()->getOrganizations();
        if ($this->escort->hasPrivilege('pr.staff.see.all')) {
            // Select organization
            $options = array('' => $this->_('(all organizations)')) + $availableOrganizations;
        } else {
            $allowedOrganizations   = $this->loader->getCurrentUser()->getAllowedOrganizations();
            $options = array_intersect($availableOrganizations, $allowedOrganizations);
        }

        if (count($options)>1) {
            $select = new Zend_Form_Element_Select('gsf_id_organization', array('multiOptions' => $options));

            // Position as second element
            $search = array_shift($elements);
            array_unshift($elements, $search, $select);
        }

        return $elements;
    }

    /**
     * Returns the default search values for this class instance.
     *
     * Used to specify the filter when no values have been entered by the user.
     *
     * @return array
     */
    public function getDefaultSearchData()
    {
        $filter = parent::getDefaultSearchData();

        if (!isset($filter['gsf_id_organization']) || empty($filter['gsf_id_organization'])) {
            $filter['gsf_id_organization'] = $this->loader->getCurrentUser()->getCurrentOrganizationId();
        }

        return $filter;
    }

    public function getInstanceId()
    {
        if ($this->_instanceId) {
            return $this->_instanceId;
        }

        return parent::getInstanceId();
    }

    /**
     * Return an old style (< 1.5) hashed version of the input value.
     *
     * @param string $value The value to hash.
     * @param boolean $new Optional is new, is here for ModelAbstract setOnSave compatibility
     * @param string $name Optional name, is here for ModelAbstract setOnSave compatibility
     * @param array $context Optional, the other values being saved
     * @return string The salted hash as a 32-character hexadecimal number.
     */
    public function getOldPasswordHash($value, $isNew = false, $name = null, array $context = array())
    {
        return md5($value);
    }

    public function getTopic($count = 1)
    {
        return $this->plural('staff member', 'staff members', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Staff');
    }

    /**
     * Load the user selected by the request - if any
     */
    protected function loadUser()
    {
        if ($staff_id = $this->_getIdParam()) {
            $this->_user = $this->loader->getUserLoader()->getUserByStaffId($staff_id);
            $source      = $this->menu->getParameterSource();
            $source->offsetSet('gsf_id_organization', $this->_user->getBaseOrganizationId());
            $source->offsetSet('accessible_role',     $this->_user->hasAllowedRole());
        }
    }


    public function mailAction() {
        $this->loadUser();

        $params['mailTarget']   = 'staff';
        $params['menu']         = $this->menu;
        $params['model']        = $this->getModel();
        $params['identifier']   = array($this->_getIdParam());
        $params['view']         = $this->view;
        $params['routeAction']  = 'show';
        $params['formTitle']    = sprintf($this->_('Send mail to: %s'), $this->_user->getFullName());


        $this->addSnippet('Mail_MailFormSnippet', $params);
    }


    /**
     * Action to allow password reset
     */
    public function resetAction()
    {
        // Make sure the user is loaded
        $this->loadUser();

        $this->html->h3(sprintf($this->_('Reset password for: %s'), $this->_user->getFullName()));

        if (! ($this->_user->hasAllowedRole() && $this->_user->canSetPassword())) {
            $this->addMessage($this->_('You are not allowed to change this password.'));
            return;
        }

        /*************
         * Make form *
         *************/
        $form = $this->_user->getChangePasswordForm(array(
            'askOld'     => false,
            'forceRules' => false    // If user logs in using password that does not obey the rules, he is forced to change it
            ));

        $createElement = new MUtil_Form_Element_FakeSubmit('create_account');
        $createElement->setLabel($this->translate->_('Create account mail'))
                    ->setAttrib('class', 'button')
                    ->setOrder(0);

        $form->addElement($createElement);

        $resetElement = new MUtil_Form_Element_FakeSubmit('reset_password');
        $resetElement->setLabel($this->translate->_('Reset password mail'))
                    ->setAttrib('class', 'button')
                    ->setOrder(1);
        $form->addElement($resetElement);

        /****************
         * Process form *
         ****************/
        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();
            // MUtil_Echo::track($data);
            if (isset($data['create_account']) && $data['create_account']) {
                $mail = $this->loader->getMailLoader()->getMailer('staffPassword', $this->_getIdParam());
                $mail->setOrganizationFrom();
                if ($mail->setCreateAccountTemplate()) {
                    $mail->send();
                    $this->addMessage($this->_('Mail sent'));
                    $this->_reroute(array($this->getRequest()->getActionKey() => 'show'));
                } else {
                    $this->addMessage($this->_('No default Create Account mail template set in organization or project'));
                }

            } elseif (isset($data['reset_password']) && $data['reset_password']) {
                $mail = $this->loader->getMailLoader()->getMailer('staffPassword', $this->_getIdParam());
                $mail->setOrganizationFrom();
                if ($mail->setResetPasswordTemplate()) {
                    $mail->send();
                    $this->addMessage($this->_('Mail sent'));
                    $this->_reroute(array($this->getRequest()->getActionKey() => 'show'));
                } else {
                    $this->addMessage($this->_('No default Reset Password mail template set in organization or project'));
                }


            } elseif ($form->isValid($data, false)) {
                // If form is valid, but contains messages, do show them. Most likely these are the not enforced password rules
                if ($form->getMessages()) {
                    $this->addMessage($form->getMessages());
                }
                $this->addMessage($this->_('New password is active.'));
                $this->_reroute(array($this->getRequest()->getActionKey() => 'show'));

            } else {
                $this->addMessage($form->getErrorMessages());
            }
        }

        /****************
         * Display form *
         ****************/
        if ($this->_user->isPasswordResetRequired()) {
            $this->menu->setVisible(false);
        } else {
            $form->addButtons($this->createMenuLinks());
        }
        $this->beforeFormDisplay($form, false);

        $this->html[] = $form;
    }
}
