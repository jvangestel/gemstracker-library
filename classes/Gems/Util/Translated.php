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
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Translated.php 430 2011-08-18 10:40:21Z 175780 $
 */

/**
 * Provides translated strings for default options like gender and takes care of date/time formatting
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Util_Translated extends Gems_Registry_TargetAbstract
{
    protected $phpDateFormatString = 'd-m-Y';

    /**
     *
     * @var Zend_Translate
     */
    protected $translate;

    public $dateFormatString = 'yyyy-MM-dd';
    public $dateTimeFormatString = 'yyyy-MM-dd HH:mm:ss';

    public static $emptyDropdownArray;

    protected function _($text, $locale = null)
    {
        return $this->translate->_($text, $locale);
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * Makes sure $this->translate is set.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if (! $this->translate instanceof Zend_Translate) {
            $this->translate = Zend_Registry::get('Zend_Translate');

            if (! $this->translate instanceof Zend_Translate) {
                $this->translate = new MUtil_Translate_Adapter_Potemkin();
            }
        }
        self::$emptyDropdownArray = array('' => $this->_('-'));
            
        return parent::checkRegistryRequestsAnswers();
    }

    public function formatDate($dateValue)
    {
        return $this->formatDateTime($dateValue);
    }

    public function formatDateForever($dateValue)
    {
        if ($dateValue) {
            return $this->formatDateTime($dateValue);
        } else {
            return MUtil_Html::create()->span($this->_('forever'), array('class' => 'disabled'));
        }
    }

    public function formatDateNa($dateValue)
    {
        if ($dateValue) {
            return $this->formatDateTime($dateValue);
        } else {
            return MUtil_Html::create()->span($this->_('n/a'), array('class' => 'disabled'));
        }
    }

    public function formatDateNever($dateValue)
    {
        if ($dateValue) {
            return $this->formatDateTime($dateValue);
        } else {
            return MUtil_Html::create()->span($this->_('never'), array('class' => 'disabled'));
        }
    }

    public function formatDateUnknown($dateValue)
    {
        if ($dateValue) {
            return $this->formatDateTime($dateValue);
        } else {
            return MUtil_Html::create()->span($this->_('unknown'), array('class' => 'disabled'));
        }
    }

    public function formatDateTime($dateTimeValue)
    {
        if ($dateTimeValue) {
            //$dateTime = strtotime($dateTimeValue);
            // MUtil_Echo::track($dateTimeValue, date('c', $dateTime), $dateTime / 86400, date('c', time()), time() / 86400);
            // TODO: Timezone seems to screw this one up          
            //$days = floor($dateTime / 86400) - floor(time() / 86400); // 86400 = 24*60*60            
            $dateTime = new MUtil_Date($dateTimeValue, Zend_Date::ISO_8601);
            $days = $dateTime->diffDays(new MUtil_Date());
            
            switch ($days) {
                case -2:
                    return $this->_('2 days ago');

                case -1:
                    return $this->_('Yesterday');

                case 0:
                    return $this->_('Today');

                case 1:
                    return $this->_('Tomorrow');

                case 2:
                    return $this->_('Over 2 days');

                default:
                    if (($days > -14) && ($days < 14)) {
                        if ($days > 0) {
                            return sprintf($this->_('Over %d days'), $days);
                        } else {
                            return sprintf($this->_('%d days ago'), -$days);
                        }
                    }

                    return date($this->phpDateFormatString, $dateTime->getTimestamp()); //  . ' (' . $days . ')';
            }
        }

        return null;
    }

    /**
     * Get a translated empty value for usage in dropdowns
     * 
     * On instantiation of the class via Gems_Loader this variable will be populated
     * in checkRegistryRequestsAnswers
     *
     * @return array
     */
    public function getEmptyDropdownArray()
    {
        return self::$emptyDropdownArray;
    }

    public function getGenders()
    {
        return array('M' => $this->_('Male'), 'F' => $this->_('Female'), 'U' => $this->_('Unknown'));
    }

    public function getGenderGreeting()
    {
        return array('M' => $this->_('mr.'), 'F' => $this->_('mrs.'), 'U' => $this->_('mr./mrs.'));
    }

    public function getGenderHello()
    {
        return array('M' => $this->_('Mr.'), 'F' => $this->_('Mrs.'), 'U' => $this->_('Mr./Mrs.'));
    }

    public function getYesNo()
    {
        static $data;

        if (! $data) {
            $data = array(1 => $this->_('Yes'), 0 => $this->_('No'));
        }

        return $data;
    }

    // DO NOT USE THIS FUNCTION
    // It will screw up your automatically generated translations file.
    // As I have done so twice, I leave this here as a reminder. Matijs
    /*
    public static function translateArray(Zend_Translate $translate, array $texts)
    {
        foreach ($texts as &$text) {
            $text = $translate->_($text);
        }

        return $texts;
    } // */
}
