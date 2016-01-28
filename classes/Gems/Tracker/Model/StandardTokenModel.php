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
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

use MUtil\Model\Dependency\OffOnElementsDependency;

/**
 * The StandardTokenModel is the model used to display tokens
 * in e.g. browse tables. It can also be used to edit standard
 * tokens, though track engines may supply different models for
 * editing, as the SingleSurveyTokeModel does.
 *
 * The standard token model combines all possible information
 * about the token from the tables:
 * - gems__groups
 * - gems__organizations
 * - gems__reception_codes
 * - gems__respondent2org
 * - gems__respondent2track
 * - gems__respondents
 * - gems__staff (on created by)
 * - gems__surveys
 * - gems__tracks
 *
 * The \MUtil_Registry_TargetInterface is implemented so that
 * these models can take care of their own formatting.
 *
 * @see \Gems_Tracker_Engine_TrackEngineInterface
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Model_StandardTokenModel extends \Gems_Model_HiddenOrganizationModel
{
    /**
     *
     * @var boolean Set to true when data in the respondent2track table must be saved as well
     */
    protected $saveRespondentTracks = false;

    /**
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Create the model with standard tables and calculated columns
     */
    public function __construct()
    {
        parent::__construct('token', 'gems__tokens', 'gto');

        if ($this->saveRespondentTracks) {
            // Set the correct prefix
            $this->saveRespondentTracks = 'gr2t';
        }

        $this->addTable(    'gems__tracks',               array('gto_id_track' => 'gtr_id_track'));
        $this->addTable(    'gems__surveys',              array('gto_id_survey' => 'gsu_id_survey'));
        $this->addTable(    'gems__groups',               array('gsu_id_primary_group' => 'ggp_id_group'));
        $this->addTable(    'gems__respondents',          array('gto_id_respondent' => 'grs_id_user'));
        $this->addTable(    'gems__respondent2org',       array('gto_id_organization' => 'gr2o_id_organization', 'gto_id_respondent' => 'gr2o_id_user'));
        $this->addTable(    'gems__respondent2track',     array('gto_id_respondent_track' => 'gr2t_id_respondent_track'), $this->saveRespondentTracks);
        $this->addTable(    'gems__organizations',        array('gto_id_organization' => 'gor_id_organization'));
        $this->addTable(    'gems__reception_codes',      array('gto_reception_code' => 'grc_id_reception_code'));
        $this->addTable(    'gems__rounds',               array('gto_id_round' => 'gro_id_round'));
        $this->addLeftTable('gems__staff',                array('gr2t_created_by' => 'gems__staff.gsf_id_user'));
        $this->addLeftTable('gems__track_fields',         array('gto_id_relationfield' => 'gtf_id_field', 'gtf_field_type = "relation"'));       // Add relation fields
        $this->addLeftTable('gems__respondent_relations', array('gto_id_relation' => 'grr_id', 'gto_id_respondent' => 'grr_id_respondent')); // Add relation

        $this->addColumn(
            "CASE WHEN CHAR_LENGTH(gsu_survey_name) > 30 THEN CONCAT(SUBSTRING(gsu_survey_name, 1, 28), '...') ELSE gsu_survey_name END",
            'survey_short',
            'gsu_survey_name');
        $this->addColumn(
            "CASE WHEN gsu_survey_pdf IS NULL OR CHAR_LENGTH(gsu_survey_pdf) = 0 THEN 0 ELSE 1 END",
            'gsu_has_pdf');

        $this->addColumn(
            'CASE WHEN gto_completion_time IS NULL THEN 0 ELSE 1 END',
            'is_completed');
        $this->addColumn(
            'CASE WHEN grc_success = 1 AND gto_valid_from <= CURRENT_TIMESTAMP AND gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) THEN 1 ELSE 0 END',
            'can_be_taken');
        $this->addColumn(
            "CASE WHEN grc_success = 1 THEN '' ELSE 'deleted' END",
            'row_class');
        $this->addColumn(
            "CASE WHEN grc_success = 1 AND ((grs_email IS NOT NULL AND grs_email != '' and gto_id_relationfield IS NULL) OR (grr_email IS NOT NULL AND grr_email != '' and gto_id_relationfield IS NOT NULL)) AND ggp_respondent_members = 1 AND gto_valid_from <= CURRENT_TIMESTAMP AND gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) AND gr2o_mailable = 1 AND gr2t_mailable = 1 THEN 1 ELSE 0 END",
            'can_email');

        $this->addColumn(
            "TRIM(CONCAT(COALESCE(CONCAT(grs_last_name, ', '), '-, '), COALESCE(CONCAT(grs_first_name, ' '), ''), COALESCE(grs_surname_prefix, '')))",
            'respondent_name');
        $this->addColumn(
            "CASE WHEN gems__staff.gsf_id_user IS NULL
                THEN '-'
                ELSE
                    CONCAT(
                        COALESCE(gems__staff.gsf_last_name, ''),
                        ', ',
                        COALESCE(gems__staff.gsf_first_name, ''),
                        COALESCE(CONCAT(' ', gems__staff.gsf_surname_prefix), '')
                    )
                END",
            'assigned_by');
        $this->addColumn(new \Zend_Db_Expr("'token'"), \Gems_Model::ID_TYPE);
        /*    TRIM(CONCAT(
                CASE WHEN gto_created = gto_changed OR DATEDIFF(CURRENT_TIMESTAMP, gto_changed) > 0 THEN '' ELSE 'changed' END,
                ' ',
                CASE WHEN DATEDIFF(CURRENT_TIMESTAMP, gto_created) > 0 THEN '' ELSE 'created' END
            ))"), 'row_class'); // */

        if ($this->saveRespondentTracks) {
            // The save order is reversed in this case.
            $this->_saveTables = array_reverse($this->_saveTables);
        }

        $this->set('gsu_id_primary_group', 'default', 800);

        $this->setOnSave('gto_mail_sent_date', array($this, 'saveCheckedMailDate'));
        $this->setOnSave('gto_mail_sent_num',  array($this, 'saveCheckedMailNum'));

        $this->useTokenAsKey();
    }

    /**
     * Function to check whether the mail_sent should be reset
     *
     * @param boolean $isNew True when a new item is being saved
     * @param array $context The values being saved
     * @return boolean True when the change should be triggered
     */
    private function _checkForMailSent($isNew, array $context)
    {
        // Never change on new tokens
        if ($isNew) {
            return false;
        }

        // Only act on existing valid from date
        if (! (isset($context['gto_valid_from']) && $context['gto_valid_from'])) {
            return false;
        }

        // There must be data to reset
        $hasSentDate = isset($context['gto_mail_sent_date']) && $context['gto_mail_sent_date'];
        if (! ($hasSentDate || (isset($context['gto_mail_sent_num']) && $context['gto_mail_sent_num']))) {
            return false;
        }

        // When only the sent_num is set, then clear the existing data
        if (! $hasSentDate) {
            return true;
        }

        if ($context['gto_valid_from'] instanceof \Zend_Date) {
            $start = $context['gto_valid_from'];
        } else {
            $start = new \MUtil_Date($context['gto_valid_from'], $this->get('gto_valid_from', 'dateFormat'));
        }

        if ($context['gto_mail_sent_date'] instanceof \Zend_Date) {
            $sent = $context['gto_mail_sent_date'];
        } else {
            $sent = new \MUtil_Date($context['gto_mail_sent_date'], $this->get('gto_mail_sent_date', 'dateFormat'));
        }

        return $start->isLater($sent);
    }

    /**
     * Add tracking off manual date changes by the user
     *
     * @param mixed $value The value to store when the tracked field has changed
     * @return \Gems_Tracker_Model_StandardTokenModel
     */
    public function addEditTracking()
    {
//        Old code
//        $changer = new \MUtil_Model_Type_ChangeTracker($this, 1, 0);
//
//        $changer->apply('gto_valid_from_manual',  'gto_valid_from');
//        $changer->apply('gto_valid_until_manual', 'gto_valid_until');

        $this->addDependency(new OffOnElementsDependency('gto_valid_from_manual',  'gto_valid_from', 'readonly', $this));
        $this->addDependency(new OffOnElementsDependency('gto_valid_until_manual', 'gto_valid_until', 'readonly', $this));

        $this->set('gto_valid_until', 'validators[dateAfter]',
              new \MUtil_Validate_Date_DateAfter('gto_valid_from')
              );

        return $this;
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();
        
        //If we are allowed to see who filled out a survey, modify the model accordingly
        if ($this->user->hasPrivilege('pr.respondent.who')) {
            $this->addLeftTable('gems__staff', array('gto_by' => 'gems__staff_2.gsf_id_user'));
            $this->addColumn(new \Zend_Db_Expr('CASE
                WHEN gems__staff_2.gsf_id_user IS NULL THEN COALESCE(gems__track_fields.gtf_field_name, gems__groups.ggp_name)
                ELSE COALESCE(CONCAT_WS(
                    " ",
                    CONCAT(COALESCE(gems__staff_2.gsf_last_name, "-"), ","),
                    gems__staff_2.gsf_first_name,
                    gems__staff_2.gsf_surname_prefix
                    ))
                END'), 'ggp_name');
        } else {
            $this->set('ggp_name', 'column_expression', new \Zend_Db_Expr('COALESCE(gems__track_fields.gtf_field_name, gems__groups.ggp_name)'));
        }
        if ($this->user->hasPrivilege('pr.respondent.result')) {
            $this->addColumn('gto_result', 'calc_result', 'gto_result');
        } else {
            $this->addColumn(new \Zend_Db_Expr('NULL'), 'calc_result', 'gto_result');
        }
    }

    /**
     * Sets the labels, format functions, etc...
     *
     * @return \Gems_Tracker_Model_StandardTokenModel
     */
    public function applyFormatting()
    {
        $this->resetOrder();

        $dbLookup   = $this->util->getDbLookup();
        $translated = $this->util->getTranslated();

        // Token id & respondent
        $this->set('gto_id_token',           'label', $this->_('Token'),
                'elementClass', 'Exhibitor',
                'formatFunction', 'strtoupper'
                );
        $this->set('gr2o_patient_nr',        'label', $this->_('Respondent nr'),
                'elementClass', 'Exhibitor'
                );
        $this->set('respondent_name',        'label', $this->_('Respondent name'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gto_id_organization',    'label', $this->_('Organization'),
                'elementClass', 'Exhibitor',
                'multiOptions', $dbLookup->getOrganizationsWithRespondents()
                );

        // Track, round & survey
        $this->set('gtr_track_name',         'label', $this->_('Track'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gr2t_track_info',        'label', $this->_('Description'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gto_round_description',  'label', $this->_('Round'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gsu_survey_name',        'label', $this->_('Survey'),
                'elementClass', 'Exhibitor'
                );
        $this->set('ggp_name',               'label', $this->_('Assigned to'),
                'elementClass', 'Exhibitor'
                );

        // Token, editable part
        $manual = $translated->getDateCalculationOptions();
        $this->set('gto_valid_from_manual',  'label', $this->_('Set valid from'),
                'description', $this->_('Manually set dates are fixed an will never be (re)calculated.'),
                'elementClass', 'Radio',
                'multiOptions', $manual,
                'separator', ' '
                );
        $this->set('gto_valid_from',         'label', $this->_('Valid from'),
                'elementClass', 'Date',
                'formatFunction', $translated->formatDateNever,
                'tdClass', 'date');
        $this->set('gto_valid_until_manual', 'label', $this->_('Set valid until'),
                'description', $this->_('Manually set dates are fixed an will never be (re)calculated.'),
                'elementClass', 'Radio',
                'multiOptions', $manual,
                'separator', ' '
                );
        $this->set('gto_valid_until',        'label', $this->_('Valid until'),
                'elementClass', 'Date',
                'formatFunction', $translated->formatDateForever,
                'tdClass', 'date');
        $this->set('gto_comment',            'label', $this->_('Comments'),
                'cols', 50,
                'elementClass', 'Textarea',
                'rows', 3,
                'tdClass', 'pre'
                );

        // Token, display part
        $this->set('gto_mail_sent_date',     'label', $this->_('Last contact'),
                'elementClass', 'Exhibitor',
                'formatFunction', $translated->formatDateNever,
                'tdClass', 'date');
        $this->set('gto_mail_sent_num',      'label', $this->_('Number of contact moments'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gto_completion_time',    'label', $this->_('Completed'),
                'elementClass', 'Exhibitor',
                'formatFunction', $translated->formatDateNa,
                'tdClass', 'date');
        $this->set('gto_duration_in_sec',    'label', $this->_('Duration in seconds'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gto_result',             'label', $this->_('Score'),
                'elementClass', 'Exhibitor'
                );
        $this->set('grc_description',        'label', $this->_('Reception code'),
                'formatFunction', array($this->translate, '_'),
                'elementClass', 'Exhibitor'
                );
        $this->set('gto_changed',            'label', $this->_('Changed on'),
                'elementClass', 'Exhibitor',
                'formatFunction', $translated->formatDateUnknown
                );
        $this->set('assigned_by',            'label', $this->_('Assigned by'),
                'elementClass', 'Exhibitor'
                );

        return $this;
    }

    /**
     * Sets the labels, format functions, etc...
     *
     * @return \Gems_Tracker_Model_StandardTokenModel
     */
    public function applyInsertionFormatting()
    {
        $this->set('gto_id_token', 'elementClass', 'None');

        return $this;
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->translate && $this->util;
    }

    /**
     * A ModelAbstract->setOnSave() function that can transform the saved item.
     *
     * @see setSaveWhen()
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return boolean
     */
    public function saveCheckedMailDate($value, $isNew = false, $name = null, array $context = array())
    {
        if ($this->_checkForMailSent($isNew, $context)) {
            return null;
        }

        return $this->formatSaveDate($value, $isNew, $name, $context);
    }

    /**
     * A ModelAbstract->setOnSave() function that can transform the saved item.
     *
     * @see setSaveWhen()
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return boolean
     */
    public function saveCheckedMailNum($value, $isNew = false, $name = null, array $context = array())
    {
        if ($this->_checkForMailSent($isNew, $context)) {
            return 0;
        }

        return $value;
    }

    public function useRespondentTrackAsKey()
    {
        $this->setKeys($this->_getKeysFor('gems__respondent2org') + $this->_getKeysFor('gems__tracks'));

        return $this;
    }

    public function useTokenAsKey()
    {
        $this->setKeys($this->_getKeysFor('gems__tokens'));

        return $this;
    }
}