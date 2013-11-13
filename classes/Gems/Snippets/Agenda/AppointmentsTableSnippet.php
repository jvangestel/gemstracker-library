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
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: AppointmentsTableSnippet.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Snippets_Agenda_AppointmentsTableSnippet extends Gems_Snippets_ModelTableSnippetAbstract
{
    /**
     * Date storage format string
     *
     * @var string
     */
    private $_dateStorageFormat;

    /**
     * Image for time display
     *
     * @var MUtil_Html_HtmlElement
     */
    private $_timeImg;

    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(MUtil_Model_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $bridge->gr2o_patient_nr;
        $bridge->gr2o_id_organization;

        if ($menuItem = $this->menu->find(array('controller' => 'appointment', 'action' => 'show', 'allowed' => true))) {
            $appButton = $menuItem->toActionLink($this->request, $bridge, $this->_('Show appointment'));
        } else {
            $appButton = null;
        }
        if ($menuItem = $this->menu->find(array('controller' => 'appointment', 'action' => 'edit', 'allowed' => true))) {
            $respButton = $menuItem->toActionLink($this->request, $bridge, $this->_('Edit appointment'));
        } else {
            $respButton = null;
        }

        $br    = MUtil_Html::create('br');

        $table = $bridge->getTable();
        $table->appendAttrib('class', 'calendar');

        $bridge->tr()->appendAttrib('class', $bridge->row_class);
        $bridge->addColumn($appButton)->class = 'middleAlign';
        $bridge->addMultiSort('date_only', 'gap_admission_time');
        $bridge->addMultiSort('gas_name', $br, 'glo_name');
        $bridge->addMultiSort('gaa_name', $br, 'gapr_name');
        // $bridge->addColumn(array($bridge->gaa_name, $br, $bridge->gapr_name));
        $bridge->addColumn($respButton)->class = 'middleAlign rightAlign';

        unset($table[MUtil_Html_TableElement::THEAD]);
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

        $this->onEmpty = $this->_('No appointments found.');
    }

    /**
     * Creates the model
     *
     * @return MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = $this->loader->getModels()->createAppointmentModel();
        $model->applyBrowseSettings();

        $model->addColumn(new Zend_Db_Expr("CONVERT(gap_admission_time, DATE)"), 'date_only');
        $model->set('date_only', 'formatFunction', array($this, 'formatDate'));
                // 'dateFormat', Zend_Date::DAY_SHORT . ' ' . Zend_Date::MONTH_NAME . ' ' . Zend_Date::YEAR);
        $model->set('gap_admission_time', 'label', $this->_('Time'),
                'formatFunction', array($this, 'formatTime'));
                // 'dateFormat', 'HH:mm ' . Zend_Date::WEEKDAY);

        $this->_dateStorageFormat = $model->get('gap_admission_time', 'storageFormat');
        $this->_timeImg           = MUtil_Html::create('img', array('src' => 'stopwatch.png'));

        $model->set('gr2o_patient_nr', 'label', $this->_('Respondent nr'));

        return $model;
    }

    /**
     * Display the date field
     *
     * @param MUtil_Date $value
     */
    public function formatDate($value)
    {
        return MUtil_Html::create(
                'span',
                array('class' => 'date'),
                MUtil_Date::format(
                        $value,
                        Zend_Date::DAY_SHORT . ' ' . Zend_Date::MONTH_NAME . ' ' . Zend_Date::YEAR,
                        $this->_dateStorageFormat
                        )
                );
    }

    /**
     * Display the time field
     *
     * @param MUtil_Date $value
     */
    public function formatTime($value)
    {
        return MUtil_Html::create(
                'span',
                array('class' => 'time'),
                $this->_timeImg,
                MUtil_Date::format($value, ' HH:mm ' . Zend_Date::WEEKDAY, $this->_dateStorageFormat)
                );
    }

    /**
     * Returns an edit menu item, if access is allowed by privileges
     *
     * @return Gems_Menu_SubMenuItem
     */
    protected function getEditMenuItem()
    {
        return $this->menu->find(array('controller' => 'appointment', 'action' => 'edit'));
    }


    /**
     * Returns a show menu item, if access is allowed by privileges
     *
     * @return Gems_Menu_SubMenuItem
     */
    protected function getShowMenuItem()
    {
        return $this->menu->find(array('controller' => 'appointment', 'action' => 'show'));
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(MUtil_Model_ModelAbstract $model)
    {
        $model->setFilter(array(
            'gr2o_patient_nr'      => $this->request->getParam(MUtil_Model::REQUEST_ID1),
            'gr2o_id_organization' => $this->request->getParam(MUtil_Model::REQUEST_ID2),
            // 'gap_status'           => $this->loader->getAgenda()->getStatusKeysActive(),
            ));
    }
}
