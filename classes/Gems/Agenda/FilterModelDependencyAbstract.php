<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: FilterModelDependencyAbstract.php $
 */

namespace Gems\Agenda;

/**
 * Default dependency for any AppointFilter
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 15-okt-2014 18:10:36
 */
// abstract class Gems_Agenda_FilterModelDependencyAbstract extends MUtil_Model_Dependency_ValueSwitchDependency
abstract class FilterModelDependencyAbstract extends \MUtil_Model_Dependency_ValueSwitchDependency
{
    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overriden in sub class
     *
     * @var array Of name => name
     */
    protected $_dependentOn = array('gaf_class');

    /**
     * The number of gaf_filter_textN fields/
     *
     * @var int
     */
    protected $_fieldCount = 4;

    /**
     * The maximum length of the calculated name
     *
     * @var int
     */
    protected $_maxNameCalcLength = 200;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $setOnSave = \MUtil_Model_ModelAbstract::SAVE_TRANSFORMER;
        $switches  = $this->getTextSettings();

        // Make sure the calculated name is saved
        if (! isset($switches['gaf_calc_name'], $switches['gaf_calc_name'][$setOnSave])) {
            $switches['gaf_calc_name'][$setOnSave] = array($this, 'calcultateAndCheckName');
        }

        // Make sure the class name is always saved
        $className = $this->getFilterClass();
        $switches['gaf_class'][$setOnSave] = $className;

        // Check all the fields
        for ($i = 1; $i <= $this->_fieldCount; $i++) {
            $field = 'gaf_filter_text' . $i;
            if (! isset($switches[$field])) {
                $switches[$field] = array('label' => null, 'elementClass' => 'Hidden');
            }
        }

        $this->addSwitches(array($className => $switches));
    }

    /**
     * A ModelAbstract->setOnSave() function that returns the input
     * date as a valid date.
     *
     * @see MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return Zend_Date
     */
    public function calcultateAndCheckName($value, $isNew = false, $name = null, array $context = array())
    {
        return substr($this->calcultateName($value, $isNew, $name, $context), 0, $this->_maxNameCalcLength);
    }

    /**
     * A ModelAbstract->setOnSave() function that returns the input
     * date as a valid date.
     *
     * @see MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return Zend_Date
     */
    abstract public function calcultateName($value, $isNew = false, $name = null, array $context = array());

    /**
     * Get the class name for the filters, the part after *_Agenda_Filter_
     *
     * @return string
     */
    abstract public function getFilterClass();

    /**
     * Get the name for this filter class
     *
     * @return string
     */
    abstract public function getFilterName();

    /**
     * Get the settings for the gaf_filter_textN fields
     *
     * Fields not in this array are not shown in any way
     *
     * @return array gaf_filter_textN => array(modelFieldName => fieldValue)
     */
    abstract public function getTextSettings();

    /**
     * Set the maximum length of the calculated name field
     * 
     * @param int $length
     * @return \Gems\Agenda\FilterModelDependencyAbstract
     */
    public function setMaximumCalcLength($length = 200)
    {
        $this->_maxNameCalcLength = $length;

        return $this;
    }
}