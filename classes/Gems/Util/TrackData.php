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
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Class for general track utility functions
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Util_TrackData extends Gems_Registry_TargetAbstract
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Zend_Translate
     */
    protected $translate;

    /*
    public function getStartDates()
    {
        $db = $this->db;
        $t  = $this->translate;

        $table = new Zend_DB_Table('gems__respondents');

        $lq = Gems_Util::decodeHtml('&laquo;');
        $rq = Gems_Util::decodeHtml('&raquo;');
        $dates[''] = $lq . $t->_('manual assignment') . $rq;
        $dates['gtr_start_date'] = $lq . $t->_('earliest date') . $rq;
        $dates['grs_created'] = $lq . $t->_('respondent creation') . $rq;

        foreach ($table->info('metadata') as $field) {
            if ('date' === strtolower($field['DATA_TYPE'])) {
                $name = $field['COLUMN_NAME'];
                $dates[$name] = $t->_($name);
            }
        }

        return $dates;
    } // */

    /*
    public function getActiveSurveys()
    {
        static $surveys;

        if (! $surveys) {
            $surveys = $this->util->getTranslated()->getEmptyDropdownArray();
            $surveys = $surveys + $this->db->fetchPairs('SELECT gsu_id_survey, gsu_survey_name FROM gems__surveys WHERE gsu_active = 1 AND gsu_surveyor_active = 1 ORDER BY gsu_survey_name');
        }

        return $surveys;
    } // */


    /**
     * Returns array (id => name) of all ronds in all tracks, sorted by order
     *
     * @return array
     */
    public function getAllRounds()
    {
        static $rounds;
        if (! $rounds) {
            $rounds = $this->db->fetchPairs("SELECT gro_id_round, CONCAT(gro_id_order, ' - ', SUBSTR(gsu_survey_name, 1, 80)) AS name FROM gems__rounds INNER JOIN gems__surveys ON gro_id_survey = gsu_id_survey ORDER BY gro_id_order");
        }

        return $rounds;
    }

    /**
     * Retrieve an array of key/value pairs for gsu_id_survey and gsu_survey_name
     *
     * @staticvar array $surveys
     * @return array
     */
    public function getAllSurveys()
    {
        static $surveys;

        if (! $surveys) {
            $surveys = $this->db->fetchPairs('SELECT gsu_id_survey, gsu_survey_name FROM gems__surveys ORDER BY gsu_survey_name');
        }

        return $surveys;
    }

    /**
     * Retrieve an array of key/value pairs for gsu_id_survey and gsu_survey_name plus gsu_survey_description
     *
     * @staticvar array $surveys
     * @return array
     */
    public function getAllSurveysAndDescriptions()
    {
        static $surveys;

        if (! $surveys) {
            $surveys = $this->db->fetchPairs('SELECT gsu_id_survey,
            	CONCAT(
            		LEFT(CONCAT_WS(
            			" - ", gsu_survey_name, CASE WHEN LENGTH(TRIM(gsu_survey_description)) = 0 THEN NULL ELSE gsu_survey_description END
            		), 50),
        			CASE WHEN gsu_active = 1 THEN " (' . $this->translate->_('Active') . ')" ELSE " (' . $this->translate->_('Inactive') . ')" END
    			)
            	FROM gems__surveys ORDER BY gsu_survey_name');
        }

        return $surveys;
    }

    /**
     * Returns array (id => name) of all tracks, sorted alphabetically
     * @return array
     */
    public function getAllTracks()
    {
        static $tracks;

        if (! $tracks) {
            $tracks = $this->db->fetchPairs('SELECT gtr_id_track, gtr_track_name FROM gems__tracks ORDER BY gtr_track_name');
        }

        return $tracks;
    }

    /**
     * Returns array (id => name) of all ronds in a track, sorted by order
     *
     * @param int $trackId
     * @return array
     */
    public function getRoundsFor($trackId)
    {
        return $this->db->fetchPairs("SELECT gro_id_round, CONCAT(gro_id_order, ' - ', SUBSTR(gsu_survey_name, 1, 80)) AS name FROM gems__rounds INNER JOIN gems__surveys ON gro_id_survey = gsu_id_survey WHERE gro_id_track = ? ORDER BY gro_id_order", $trackId);
    }

    /**
     * Returns array (id => name) of all 'T' tracks, sorted alphabetically
     * @return array
     */
    public function getSteppedTracks()
    {
        static $tracks;

        if (! $tracks) {
            $tracks = $this->db->fetchPairs("SELECT gtr_id_track, gtr_track_name FROM gems__tracks WHERE gtr_track_type = 'T' ORDER BY gtr_track_name");
        }

        return $tracks;
    }
}
