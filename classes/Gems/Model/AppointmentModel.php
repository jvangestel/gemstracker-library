<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AppointmentModel.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Model_AppointmentModel extends Gems_Model_JoinModel
{
    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var Zend_Translate
     */
    protected $translate;

    /**
     * @var Gems_Util
     */
    protected $util;

    /**
     * Self constructor
     */
    public function __construct()
    {
        // gems__respondents MUST be first table for INSERTS!!
        parent::__construct('appointments', 'gems__appointments', 'gap');

        $this->addTable(
                'gems__respondent2org',
                array('gap_id_user' => 'gr2o_id_user', 'gap_id_organization' => 'gr2o_id_organization'),
                'gr20',
                false
                );

        $this->addColumn(new Zend_Db_Expr("'appointment'"), Gems_Model::ID_TYPE);
        $this->setKeys(array(Gems_Model::APPOINTMENT_ID => 'gap_id_appointment'));
    }

    /**
     * Add the join tables instead of lookup tables.
     */
    protected function _addJoinTables()
    {
        $this->addTable('gems__respondents', array('gap_id_user' => 'grs_id_user'));

        if ($this->has('gap_id_organization')) {
            $this->addTable(
                    'gems__organizations',
                    array('gap_id_organization' => 'gor_id_organization'),
                    'gor',
                    false
                    );
        }
        if ($this->has('gap_id_attended_by')) {
            $this->addLeftTable(
                    'gems__agenda_staff',
                    array('gap_id_attended_by' => 'gas_id_staff'),
                    'gas',
                    false
                    );
        }
        /*
        if ($this->has('gap_id_referred_by')) {
            $this->addLeftTable(
                    'gems__agenda_staff',
                    array('gap_id_referred_by' => 'ref_staff.gas_id_staff')
                    );
        } */
        if ($this->has('gap_id_activity')) {
            $this->addLeftTable(
                    'gems__agenda_activities',
                    array('gap_id_activity' => 'gaa_id_activity'),
                    'gap',
                    false
                    );
        }
        if ($this->has('gap_id_procedure')) {
            $this->addLeftTable(
                    'gems__agenda_procedures',
                    array('gap_id_procedure' => 'gapr_id_procedure'),
                    'gapr',
                    false
                    );
        }
        if ($this->has('gap_id_location')) {
            $this->addLeftTable(
                    'gems__locations',
                    array('gap_id_location' => 'glo_id_location'),
                    'glo',
                    false
                    );
        }
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * This function is no needed if the classes are setup correctly
     *
     * @return void
     */
    public function afterRegistry()
    {
        $agenda = $this->loader->getAgenda();

        if ($agenda) {
            $this->addColumn(
                    "CASE WHEN gap_status IN ('" .
                        implode("', '", $agenda->getStatusKeysInactive()) .
                        "') THEN 'deleted' ELSE '' END",
                    'row_class'
                    );
        }
    }

    /**
     * Set those settings needed for the browse display
     *
     * @return \Gems_Model_RespondentModel
     */
    public function applyBrowseSettings()
    {
        $this->_addJoinTables();
        $this->resetOrder();

        $agenda     = $this->loader->getAgenda();

        $this->setIfExists('gap_admission_time',     'label', $this->_('Appointment'),
                // 'formatFunction', array($this->util->getTranslated(), 'formatDateTime'),
                'dateFormat',  'dd-MM-yyyy HH:mm',
                'description', $this->_('dd-mm-yyyy hh:mm'));
        $this->setIfExists('gap_status',             'label', $this->_('Type'),
                'multiOptions', $agenda->getStatusCodes());
        $this->setIfExists('gas_name',              'label', $this->_('With'));
        $this->setIfExists('gaa_name',              'label', $this->_('Activities'));
        $this->setIfExists('gapr_name',             'label', $this->_('Procedures'));
        $this->setIfExists('glo_name',              'label', $this->_('Location'));
        $this->setIfExists('gor_name',              'label', $this->_('Organization'));
        $this->setIfExists('gap_subject',           'label', $this->_('Comment'));

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @param mixed $locale The locale for the settings
     * @param boolean $setMulti When false organization dependent multi options are nor filled.
     * @return \Gems_Model_RespondentModel
     */
    public function applyDetailSettings($locale = null, $setMulti = true)
    {
        $this->resetOrder();

        $agenda     = $this->loader->getAgenda();
        $dbLookup   = $this->util->getDbLookup();
        $empty      = $this->util->getTranslated()->getEmptyDropdownArray();

        $this->setIfExists('gap_admission_time',  'label', $this->_('Appointment'),
                'dateFormat',  'dd-MM-yyyy HH:mm',
                'description', $this->_('dd-mm-yyyy hh:mm'));
        $this->setIfExists('gap_discharge_time',  'label', $this->_('Discharge'),
                'dateFormat',  'dd-MM-yyyy HH:mm',
                'description', $this->_('dd-mm-yyyy hh:mm'));
        $this->setIfExists('gap_code',            'label', $this->_('Type'),
                'multiOptions', $agenda->getTypeCodes());
        $this->setIfExists('gap_status',          'label', $this->_('Status'),
                'multiOptions', $agenda->getStatusCodes());

        $this->setIfExists('gap_id_attended_by',  'label', $this->_('With'),
                'multiOptions', $empty + $agenda->getHealthcareStaff());
        $this->setIfExists('gap_id_referred_by',  'label', $this->_('Referrer'),
                'multiOptions', $empty + $agenda->getHealthcareStaff());
        $this->setIfExists('gap_id_activity',     'label', $this->_('Activities'));
        $this->setIfExists('gap_id_procedure',    'label', $this->_('Procedures'));
        $this->setIfExists('gap_id_location',     'label', $this->_('Location'));
        $this->setIfExists('gap_id_organization', 'label', $this->_('Organization'),
                'elementClass', 'Exhibitor',
                'multiOptions', $empty + $dbLookup->getOrganizations());
        $this->setIfExists('gap_subject',         'label', $this->_('Subject'));
        $this->setIfExists('gap_comment',         'label', $this->_('Comment'));

        if ($setMulti) {
            $this->setIfExists('gap_id_activity',     'multiOptions', $empty + $agenda->getActivities());
            $this->setIfExists('gap_id_procedure',    'multiOptions', $empty + $agenda->getProcedures());
            $this->setIfExists('gap_id_location',     'multiOptions', $empty + $agenda->getLocations());
        }

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @param int $orgId The id of the current organization
     * @param mixed $locale The locale for the settings
     * @return \Gems_Model_RespondentModel
     */
    public function applyEditSettings($orgId, $locale = null)
    {
        $this->applyDetailSettings($locale, false);

        $agenda = $this->loader->getAgenda();
        $empty  = $this->util->getTranslated()->getEmptyDropdownArray();

        $this->setIfExists('gap_id_organization', 'default', $orgId);
        $this->setIfExists('gap_admission_time',  'elementClass', 'Date');
        $this->setIfExists('gap_discharge_time',  'elementClass', 'Date');
        $this->setIfExists('gap_status',          'required', true);
        $this->setIfExists('gap_comment',         'elementClass', 'Textarea');

        $this->setIfExists('gap_id_activity',     'multiOptions', $empty + $agenda->getActivities($orgId));
        $this->setIfExists('gap_id_procedure',    'multiOptions', $empty + $agenda->getProcedures($orgId));
        $this->setIfExists('gap_id_location',     'multiOptions', $empty + $agenda->getLocations($orgId));

        return $this;
    }
}
