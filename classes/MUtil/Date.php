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
 */

/**
 * File description of TokenValidator
 *
 * @author Matijs de Jong <mjong@magnafacta.nl>
 * @since 1.0
 * @version 1.1
 * @package MUtil
 * @subpackage Date
 */

/**
 * Extends Zend_Date with extra date math functions
 *
 * @author Matijs de Jong <mjong@magnafacta.nl>
 * @package MUtil
 * @subpackage Date
 */
class MUtil_Date extends Zend_Date
{
    const DAY_SECONDS = 86400;      // 24 * 60 * 60
    const WEEK_SECONDS = 604800;    // 7 * 24 * 60 * 60

    /**
     * The number of days in $date subtracted from $this.
     *
     * Zero when both date/times occur on the same day.
     * POSITIVE when $date is YOUNGER than $this
     * Negative when $date is older than $this
     *
     * @param Zend_Date $date
     * @param Zend_Locale $locale optional (not used)
     * @return type
     */
    public function diffDays(Zend_Date $date, $locale = null)
    {
        $day1 = clone $this;
        $day2 = clone $date;
        $day1->setTime(0);
        $day2->setTime(0);

        $val1 = intval($day1->getUnixTimestamp() / self::DAY_SECONDS);
        $val2 = intval($day2->getUnixTimestamp() / self::DAY_SECONDS);

        return $val1 - $val2;
    }

    /**
     * The number of months in $date subtracted from $this.
     *
     * Zero when both date/times occur in the same month.
     * POSITIVE when $date is YOUNGER than $this
     * Negative when $date is older than $this
     *
     * @param Zend_Date $date
     * @param Zend_Locale $locale optional
     * @return type
     */
    public function diffMonths(Zend_Date $date, $locale = null)
    {
        $val1 = (intval($this->get(Zend_Date::YEAR, $locale)) * 12) + intval($this->get(Zend_Date::MONTH, $locale));
        $val2 = (intval($date->get(Zend_Date::YEAR, $locale)) * 12) + intval($date->get(Zend_Date::MONTH, $locale));

        return $val1 - $val2;
    }

    /**
     * The number of seconds in $date subtracted from $this.
     *
     * Zero when both date/times occur on the same second.
     * POSITIVE when $date is YOUNGER than $this
     * Negative when $date is older than $this
     *
     * @param Zend_Date $date Date or now
     * @param Zend_Locale $locale optional (not used)
     * @return type
     */
    public function diffSeconds(Zend_Date $date = null, $locale = null)
    {
        $val1 = $this->getUnixTimestamp();
        if (null == $date) {
            $val2 = time();
        } else {
            $val2 = $date->getUnixTimestamp();
        }

        return $val1 - $val2;
    }

    /**
     * The number of weeks in $date subtracted from $this.
     *
     * Zero when both date/times occur in the same week.
     * POSITIVE when $date is YOUNGER than $this
     * Negative when $date is older than $this
     *
     * @param Zend_Date $date
     * @param Zend_Locale $locale optional (not used)
     * @return type
     */
    public function diffWeeks(Zend_Date $date, $locale = null)
    {
        $week1 = clone $this;
        $week2 = clone $date;
        $week1->setWeekDay(1)->setTime(0);
        $week2->setWeekDay(1)->setTime(0);

        $val1 = intval($week1->getUnixTimestamp() / self::WEEK_SECONDS);
        $val2 = intval($week2->getUnixTimestamp() / self::WEEK_SECONDS);

        return $val1 - $val2;
    }

    /**
     * The number of the year in $date subtracted from $this.
     *
     * Zero when both date/times occur in the same year.
     * POSITIVE when $date is YOUNGER than $this
     * Negative when $date is older than $this
     *
     * @param Zend_Date $date
     * @param Zend_Locale $locale optional
     * @return type
     */
    public function diffYears(Zend_Date $date, $locale = null)
    {
        $val1 = intval($this->get(Zend_Date::YEAR, $locale));
        $val2 = intval($date->get(Zend_Date::YEAR, $locale));

        return $val1 - $val2;
    }

    public static function format($date, $outFormat, $inFormat = null, $localeOut = null)
    {
        if (! $date) {
            return null;
        }

        if (! $date instanceof self) {
            $date = new self($date, $inFormat);
        }

        return $date->toString($outFormat, null, $localeOut);
    }

    public function intDayOfYear($locale = null)
    {
        return intval($this->get(Zend_date::DAY_OF_YEAR, $locale));
    }

    public function intMonth($locale = null)
    {
        return intval($this->get(Zend_date::MONTH, $locale));
    }

    public function intWeek($locale = null)
    {
        return intval($this->get(Zend_date::WEEK, $locale));
    }

    public function intYear($locale = null)
    {
        return intval($this->get(Zend_date::YEAR, $locale));
    }

    /**
     * Returns the difference between this date and the given $date
     *
     * It will always round to the biggest period, so 8 days ago will result in 1 week ago
     * while 13 days ago will result in 2 weeks ago.
     *
     * @param Zend_Date $date
     * @param Zend_Translate $translate
     * @return string
     */
    public function diffReadable(Zend_Date $date, Zend_Translate $translate)
    {
        $difference = $date->getUnixTimeStamp() - $this->getUnixTimestamp();

        //second, minute, hour, day, week, month, year, decade
        $lengths = array("60", "60", "24", "7", "4.34", "12", "10");

        if ($difference > 0) { // this was in the past
            $ending = $translate->_("%s ago");
        } else { // this was in the future
            $difference = -$difference;
            $ending = $translate->_("%s to go");
        }
        
        for ($j = 0; $j < 7 && $difference >= $lengths[$j]; $j++) {
            $difference /= $lengths[$j];
        }
        
        $difference = round($difference);

        switch ($j) {
            case 0:
                $period = $translate->plural('second', 'seconds', $difference);
                break;
            case 1:
                $period = $translate->plural('minute', 'minutes', $difference);
                break;
            case 2:
                $period = $translate->plural('hour', 'hours', $difference);
                break;
            case 3:
                $period = $translate->plural('day', 'days', $difference);
                break;
            case 4:
                $period = $translate->plural('week', 'weeks', $difference);
                break;
            case 5:
                $period = $translate->plural('month', 'months', $difference);
                break;
            case 6:
                $period = $translate->plural('year', 'years', $difference);
                break;
            case 7:
                $period = $translate->plural('decade', 'decades', $difference);
                break;

            default:
                break;
        }
        $time = "$difference $period";
        $text = sprintf($ending, $time);

        return $text;
    }
}