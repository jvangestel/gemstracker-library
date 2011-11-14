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
 * @since      Class available since version 1.1
 */
class Gems_Default_TokenPlanAction extends Gems_Controller_BrowseEditAction
{
    public $defaultPeriodEnd   = 2;
    public $defaultPeriodStart = -4;
    public $defaultPeriodType  = 'W';

    public $maxPeriod = 15;
    public $minPeriod = -15;

    public $sortKey = array(
        'gto_valid_from'          => SORT_ASC,
        'gto_mail_sent_date'      => SORT_ASC,
        'respondent_name'         => SORT_ASC,
        'gr2o_patient_nr'         => SORT_ASC,
        'calc_track_name'         => SORT_ASC,
        'calc_track_info'         => SORT_ASC,
        'calc_round_description'  => SORT_ASC,
        'gto_round_order'         => SORT_ASC,
        );

    protected function addBrowseTableColumns(MUtil_Model_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $HTML  = MUtil_Html::create();

        // Row with dates and patient data
        $bridge->gtr_track_type; // Data needed for buttons

        $bridge->setDefaultRowClass(MUtil_Html_TableElement::createAlternateRowClass('even', 'even', 'odd', 'odd'));
        $bridge->addColumn($this->getTokenLinks($bridge), ' ')->rowspan = 2; // Space needed because TableElement does not look at rowspans
        $bridge->addSortable('gto_valid_from');
        $bridge->addSortable('gto_valid_until');

        $bridge->addMultiSort('gr2o_patient_nr', $HTML->raw('; '), 'respondent_name');
        $bridge->addMultiSort('ggp_name', array($this->getActionLinks($bridge)));

        $bridge->tr();
        $bridge->addSortable('gto_mail_sent_date');
        $bridge->addSortable('gto_completion_time');

        if ($this->escort instanceof Gems_Project_Tracks_SingleTrackInterface) {
            $bridge->addMultiSort('calc_round_description', $HTML->raw('; '), 'gsu_survey_name');
        } else {
            $model->set('calc_track_info', 'tableDisplay', 'smallData');
            $model->set('calc_round_description', 'tableDisplay', 'smallData');
            $bridge->addMultiSort(
                'calc_track_name', 'calc_track_info',
                $bridge->calc_track_name->if($HTML->raw(' &raquo; ')),
                'gsu_survey_name', 'calc_round_description');
        }

        $bridge->addSortable('assigned_by');
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
        // MUtil_Model::$verbose = true;
        $model = $this->loader->getTracker()->getTokenModel();;
        $model->setCreate(false);

        $model->set('gr2o_patient_nr',       'label', $this->_('Respondent'));
        $model->set('gto_round_description', 'label', $this->_('Round / Details'));
        $model->set('gto_valid_from',        'label', $this->_('Valid from'));
        $model->set('gto_valid_until',       'label', $this->_('Valid until'));
        $model->set('gto_mail_sent_date',    'label', $this->_('Contact date'));
        $model->set('respondent_name',       'label', $this->_('Name'));

        return $model;
    }

    public function emailAction()
    {
        $model   = $this->getModel();

        // Set the request cache to use the search params from the index action
        $this->getCachedRequestData(true, 'index', true);

        // Load the filters
        $this->_applySearchParameters($model);

        $sort = array(
            'grs_email'          => SORT_ASC,
            'grs_first_name'     => SORT_ASC,
            'grs_surname_prefix' => SORT_ASC,
            'grs_last_name'      => SORT_ASC,
            'gto_valid_from'     => SORT_ASC,
            'gto_round_order'    => SORT_ASC,
            'gsu_survey_name'    => SORT_ASC,
        );

        if ($tokensData = $model->load(true, $sort)) {

            $form = new Gems_Email_MultiMailForm(array(
                'escort' => $this->escort,
                'templateOnly' => ! $this->escort->hasPrivilege('pr.token.mail.freetext'),
            ));
            $form->setTokensData($tokensData);

            $wasSent = $form->processRequest($this->getRequest());

            if ($form->hasMessages()) {
                $this->addMessage($form->getMessages());
            }

            if ($wasSent) {
                if ($this->afterSaveRoute(array())) {
                    return null;
                }

            } else {
                $table = new MUtil_Html_TableElement(array('class' => 'formTable'));
                $table->setAsFormLayout($form, true, true);
                $table['tbody'][0][0]->class = 'label';  // Is only one row with formLayout, so all in output fields get class.
                if ($links = $this->createMenuLinks(10)) {
                    $table->tf(); // Add empty cell, no label
                    $linksCell = $table->tf($links);
                }

                $this->html->h3(sprintf($this->_('Email %s'), $this->getTopic()));
                $this->html[] = $form;
            }


        } else {
            $this->addMessage($this->_('No tokens found.'));
        }
    }

    public function getActionLinks(MUtil_Model_TableBridge $bridge)
    {
        // Get the other token buttons
        if ($menuItems = $this->menu->findAll(array('controller' => array('track', 'survey'), 'action' => array('email', 'answer'), 'allowed' => true))) {
            $buttons = $menuItems->toActionLink($this->getRequest(), $bridge);
            $buttons->appendAttrib('class', 'rightFloat');
        } else {
            $buttons = null;
        }
        // Add the ask button
        if ($menuItem = $this->menu->find(array('controller' => 'ask', 'action' => 'take', 'allowed' => true))) {
            $askLink = $menuItem->toActionLink($this->getRequest(), $bridge);
            $askLink->appendAttrib('class', 'rightFloat');

            if ($buttons) {
                // Show previous link if show, otherwise show ask link
                $buttons = array($buttons, $askLink);
            } else {
                $buttons = $askLink;
            }
        }

        return $buttons;
    }

    /**
     * Returns tokenplan specific autosearch fields. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(MUtil_Model_ModelAbstract $model, array $data)
    {
        $elements = parent::getAutoSearchElements($model, $data);

        if ($elements) {
            $elements[] = null; // break into separate spans
        }

        // Create date range elements
        $min  = -91;
        $max  = 91;
        $size = max(strlen($min), strlen($max));

        $options = array(
            'gto_valid_from'      => $this->_('Valid from'),
            'gto_valid_until'     => $this->_('Valid until'),
            'gto_mail_sent_date'  => $this->_('E-Mailed on'),
            'gto_completion_time' => $this->_('Completion date'),
            );
        $element = $this->_createSelectElement('date_used', $options);
        $element->class = 'minimal';
        $element->setLabel($this->_('For date'));
        $elements[] = $element;

        $element = new Zend_Form_Element_Text('period_start', array('label' => $this->_('from'), 'size' => $size - 1, 'maxlength' => $size, 'class' => 'rightAlign'));
        $element->addValidator(new Zend_Validate_Int());
        $element->addValidator(new Zend_Validate_Between($min, $max));
        $elements[] = $element;

        $element = new Zend_Form_Element_Text('period_end', array('label' => $this->_('until'), 'size' => $size - 1, 'maxlength' => $size, 'class' => 'rightAlign'));
        $element->addValidator(new Zend_Validate_Int());
        $element->addValidator(new Zend_Validate_Between($min, $max));
        $elements[] = $element;

        $options = array(
            'D' => $this->_('days'),
            'W' => $this->_('weeks'),
            'M' => $this->_('months'),
            'Y' => $this->_('years'),
            );
        $element = $this->_createSelectElement('date_type', $options);
        $element->class = 'minimal';
        $elements[] = $element;

        $joptions['change'] = new Zend_Json_Expr('function(e, ui) {
jQuery("#period_start").attr("value", ui.values[0]);
jQuery("#period_end"  ).attr("value", ui.values[1]).trigger("keyup");

}');
        $joptions['min']    = $this->minPeriod;
        $joptions['max']    = $this->maxPeriod;
        $joptions['range']  = true;
        $joptions['values'] = new Zend_Json_Expr('[jQuery("#period_start").attr("value"), jQuery("#period_end").attr("value")]');

        $element = new ZendX_JQuery_Form_Element_Slider('period', array('class' => 'periodSlider', 'jQueryParams' => $joptions));
        $elements[] = $element;

        $elements[] = null; // break into separate spans


        return array_merge($elements, $this->getAutoSearchSelectElements());
    }

    protected function getAutoSearchSelectElements()
    {
        // intval() protects against any escaping tricks
        $orgId = intval($this->_getParam('gto_id_organization', $this->escort->getCurrentOrganization()));

        $elements[] = $this->_('Select:');
        $elements[] = MUtil_Html::create('br');

        // Add track selection
        if ($this->escort instanceof Gems_Project_Tracks_MultiTracksInterface) {
            $sql = "SELECT gtr_id_track, gtr_track_name FROM gems__tracks WHERE gtr_active=1 AND gtr_track_type='T' AND INSTR(gtr_organizations, '|$orgId|') > 0";
            $elements[] = $this->_createSelectElement('gto_id_track', $sql, $this->_('(all tracks)'));
        }

        $sql = "SELECT gro_round_description, gro_round_description
                    FROM gems__rounds INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                    WHERE gro_active=1 AND
                        LENGTH(gro_round_description) > 0 AND
                        gtr_active=1 AND
                        gtr_track_type='T' AND
                        INSTR(gtr_organizations, '|$orgId|') > 0";
        $elements[] = $this->_createSelectElement('gto_round_description', $sql, $this->_('(all rounds)'));

        $sql = "SELECT gsu_id_survey, gsu_survey_name
                    FROM gems__surveys INNER JOIN gems__rounds ON gsu_id_survey = gro_id_survey
                        INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                    WHERE gsu_active=1 AND
                        gro_active=1 AND
                        gtr_active=1 AND
                        gtr_track_type='T' AND
                        INSTR(gtr_organizations, '|$orgId|') > 0";
        /* TODO: use this when we can update this list using ajax
        if (isset($data['gsu_id_primary_group'])) {
            $sql .= $this->db->quoteInto(" AND gsu_id_primary_group = ?", $data['gsu_id_primary_group']);
        } // */
        $elements[] = $this->_createSelectElement('gto_id_survey', $sql, $this->_('(all surveys)'));

        $options = array(
            'all'       => $this->_('(all actions)'),
            'notmailed' => $this->_('Not emailed'),
            'tomail'    => $this->_('To email'),
            'toremind'  => $this->_('Needs reminder'),
            'toanswer'  => $this->_('Yet to Answer'),
            'answered'  => $this->_('Answered'),
            'missed'    => $this->_('Missed'),
            );
        $elements[] = $this->_createSelectElement('main_filter', $options);

        $sql = "SELECT ggp_id_group, ggp_name
                    FROM gems__groups INNER JOIN gems__surveys ON ggp_id_group = gsu_id_primary_group
                        INNER JOIN gems__rounds ON gsu_id_survey = gro_id_survey
                        INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                    WHERE ggp_group_active = 1 AND
                        gro_active=1 AND
                        gtr_active=1 AND
                        gtr_track_type='T' AND
                        INSTR(gtr_organizations, '|$orgId|') > 0";
        $elements[] = $this->_createSelectElement('gsu_id_primary_group', $sql, $this->_('(all fillers)'));

        if (($this->escort instanceof Gems_Project_Organization_MultiOrganizationInterface) &&
            $this->escort->hasPrivilege('pr.plan.choose-org')){
            // Select organisation
            $options = $this->util->getDbLookup()->getActiveOrganizations();
            $elements[] = $this->_createSelectElement('gto_id_organization', $options);
        }

        $sql = "SELECT gsf_id_user, CONCAT(
                        COALESCE(gems__staff.gsf_last_name, ''),
                        ', ',
                        COALESCE(gems__staff.gsf_first_name, ''),
                        COALESCE(CONCAT(' ', gems__staff.gsf_surname_prefix), '')
                    ) AS gsf_name
                FROM gems__staff INNER JOIN gems__respondent2track ON gsf_id_user = gr2t_created_by
                WHERE gr2t_id_organization = $orgId AND
                    gr2t_active = 1";
        $elements[] = $this->_createSelectElement('gr2t_created_by', $sql, $this->_('(all staff)'));

        return $elements;
    }

    protected function getDataFilter(array $data)
    {
        // MUtil_Model::$verbose = true;

        //Add default filter
        $filter = array();
        $filter['gto_id_organization'] = isset($data['gto_id_organization']) ? $data['gto_id_organization'] : $this->escort->getCurrentOrganization(); // Is overruled when set in param
        $filter['gtr_active']  = 1;
        $filter['gsu_active']  = 1;
        $filter['grc_success'] = 1;

        if (isset($data['main_filter'])) {
            switch ($data['main_filter']) {
                case 'notmailed':
                    $filter['gto_mail_sent_date'] = null;
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    break;

                case 'tomail':
                    $filter[] = "grs_email IS NOT NULL AND grs_email != '' AND ggp_respondent_members = 1";
                    $filter['gto_mail_sent_date'] = null;
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    break;

                case 'toremind':
                    // $filter['can_email'] = 1;
                    $filter[] = 'gto_mail_sent_date < CURRENT_TIMESTAMP';
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    break;

                // case 'other':
                //    $filter[] = "grs_email IS NULL OR grs_email = '' OR ggp_respondent_members = 0";
                //    $filter['can_email'] = 0;
                //    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                //    break;

                case 'missed':
                    $filter[] = 'gto_valid_from <= CURRENT_TIMESTAMP';
                    $filter[] = 'gto_valid_until < CURRENT_TIMESTAMP';
                    $filter['gto_completion_time'] = null;
                    break;

                case 'answered':
                    $filter[] = 'gto_completion_time IS NOT NULL';
                    break;

                case 'toanswer':
                    $filter[] = 'gto_completion_time IS NULL';
                    break;

                default:
                    break;

            }
        }

        if (isset($data['date_type'], $data['date_used'])) {
            // Check for period selected
            switch ($data['date_type']) {
                case 'W':
                    $period_unit  = 'WEEK';
                    break;
                case 'M':
                    $period_unit  = 'MONTH';
                    break;
                case 'Y':
                    $period_unit  = 'YEAR';
                    break;
                default:
                    $period_unit  = 'DAY';
                    break;
            }

            if (! $data['date_used']) {
                $data['date_used'] = 'gto_valid_from';
            }

            $date_field  = $this->db->quoteIdentifier($data['date_used']);
            $date_filter = "DATE_ADD(CURRENT_DATE, INTERVAL ? " . $period_unit . ")";
            $filter[] = $this->db->quoteInto($date_field . ' >= '.  $date_filter, intval($data['period_start']));
            $filter[] = $this->db->quoteInto($date_field . ' <= '.  $date_filter, intval($data['period_end']));
        }

        // MUtil_Echo::track
        return $filter;
    }

    public function getDefaultSearchData()
    {
        return array(
            'date_used'           => 'gto_valid_from',
            'date_type'           => $this->defaultPeriodType,
            'gto_id_organization' => $this->escort->getCurrentOrganization(),
            'period_start'        => $this->defaultPeriodStart,
            'period_end'          => $this->defaultPeriodEnd,
            'main_filter'         => 'all',
        );
    }

    public function getTokenLinks(MUtil_Model_TableBridge $bridge)
    {
        // Get the token buttons
        if ($menuItems = $this->menu->findAll(array('controller' => array('track', 'survey'), 'action' => 'show', 'allowed' => true))) {
            $buttons = $menuItems->toActionLink($this->getRequest(), $bridge, $this->_('+'));
            $buttons->title = $bridge->gto_id_token->strtoupper();

            return $buttons;
        }
    }

    public function getTopic($count = 1)
    {
        return $this->plural('token', 'tokens', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Token planning');
    }

    public function indexAction()
    {
        // MUtil_Model::$verbose = true;

        // Check for unprocessed tokens
        $this->loader->getTracker()->processCompletedTokens(null, $this->session->user_id);

        parent::indexAction();
    }
}
