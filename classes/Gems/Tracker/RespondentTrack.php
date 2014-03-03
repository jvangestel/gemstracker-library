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

/**
 * Object representing a track assignment to a respondent.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_RespondentTrack extends Gems_Registry_TargetAbstract
{
    /**
     *
     * @var array of round_id => Gems_Tracker_Token
     */
    protected $_activeTokens = array();

    /**
     * @var Gems_Tracker_Token
     */
    protected $_checkStart;

    /**
     * If a field has a code name the value will occur both using
     * the code name and using the id.
     *
     * @var array Field data id/code => value
     */
    protected $_fieldData = null;

    /**
     *
     * @var Gems_Tracker_Token
     */
    protected $_firstToken;

    /**
     *
     * @var array The gems__respondent2track data
     */
    protected $_respTrackData;

    /**
     *
     * @var int The gems__respondent2track id
     */
    protected $_respTrackId;

    /**
     *
     * @var array of Gems_Tracker_Token
     */
    protected $_tokens;

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var Gems_Tracker
     */
    protected $tracker;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

    /**
     *
     * @param mixed $respTracksData Track Id or array containing reps2track record
     */
    public function __construct($respTracksData)
    {
        if (is_array($respTracksData)) {
            $this->_respTrackData = $respTracksData;
            $this->_respTrackId   = $respTracksData['gr2t_id_respondent_track'];
        } else {
            $this->_respTrackId = $respTracksData;
        }
    }

    /**
     * Check this respondent track for the number of tokens completed / to do
     *
     * @param int $userId Id of the user who takes the action (for logging)
     * @return int 1 if the track was changed by this code
     */
    public function _checkTrackCount($userId)
    {
        $sqlCount  = 'SELECT COUNT(*) AS count,
                SUM(CASE WHEN gto_completion_time IS NULL THEN 0 ELSE 1 END) AS completed
            FROM gems__tokens INNER JOIN
                gems__reception_codes ON gto_reception_code = grc_id_reception_code AND grc_success = 1
            WHERE gto_id_respondent_track = ?';

        $counts = $this->db->fetchRow($sqlCount, $this->_respTrackId);
        if (! $counts) {
            $counts = array('count' => 0, 'completed' => 0);
        }

        $values['gr2t_count']      = intval($counts['count']);
        $values['gr2t_completed']  = intval($counts['completed']);

        if (! $this->_respTrackData['gr2t_end_date_manual']) {
            $values['gr2t_end_date'] = $this->calculateEndDate();
        }

        if ($values['gr2t_count'] == $values['gr2t_completed']) {
            //Handle TrackCompletionEvent, send only changed fields in $values array
            $this->handleTrackCompletion($values, $userId);
        }

        // Remove unchanged values
        $this->tracker->filterChangesOnly($this->_respTrackData, $values);

        return $this->_updateTrack($values, $userId);
    }

    /**
     * Makes sure the fieldData is in $this->_fieldData
     *
     * @param boolean $reload Optional parameter to force reload.
     */
    private function _ensureFieldData($reload = false)
    {
        if ((null === $this->_fieldData) || $reload) {
            $engine    = $this->getTrackEngine();
            $fieldData = $engine->getFieldsData($this->_respTrackId);
            $fieldMap  = $engine->getFields();
            // MUtil_Echo::track($fieldData, $fieldMap);

            // Map the fielddata to the fieldcode
            foreach($fieldData as $key => $value) {
                if (isset($fieldMap[$key])) {
                    // The old name remains in the data set of course,
                    // using the code is a second occurence
                    $fieldData[$fieldMap[$key]] = $value;
                }
            }

            $this->_fieldData = $fieldData;
        }
    }

    /**
     * Makes sure the receptioncode data is part of the $this->_respTrackData
     *
     * @param boolean $reload Optional parameter to force reload or array with new values.
     */
    private function _ensureReceptionCode($reload = false)
    {
        if ($reload || (! isset($this->_respTrackData['grc_success']))) {
            if (is_array($reload)) {
                $this->_respTrackData = $reload + $this->_respTrackData;
            } else {
                $sql  = "SELECT * FROM gems__reception_codes WHERE grc_id_reception_code = ?";
                $code = $this->_respTrackData['gr2t_reception_code'];

                if ($row = $this->db->fetchRow($sql, $code)) {
                    $this->_respTrackData = $row + $this->_respTrackData;
                } else {
                    $trackId = $this->_respTrackId;
                    throw new Gems_Exception("Reception code $code is missing for track $trackId.");
                }
            }
        }
    }

    /**
     * Makes sure the respondent data is part of the $this->_respTrackData
     */
    protected function _ensureRespondentData()
    {
        if (! isset($this->_respTrackData['grs_id_user'], $this->_respTrackData['gr2o_id_user'], $this->_respTrackData['gco_code'])) {
            $sql = "SELECT *
                FROM gems__respondents INNER JOIN
                    gems__respondent2org ON grs_id_user = gr2o_id_user INNER JOIN
                    gems__consents ON gr2o_consent = gco_description
                WHERE gr2o_id_user = ? AND gr2o_id_organization = ? LIMIT 1";

            $respId = $this->_respTrackData['gr2t_id_user'];
            $orgId  = $this->_respTrackData['gr2t_id_organization'];

            if ($row = $this->db->fetchRow($sql, array($respId, $orgId))) {
                $this->_respTrackData = $this->_respTrackData + $row;
            } else {
                $trackId = $this->_respTrackId;
                throw new Gems_Exception("Respondent data missing for track $trackId.");
            }
        }
    }

    private function _updateTrack(array $values, $userId)
    {
        // MUtil_Echo::track($values);
        if ($this->tracker->filterChangesOnly($this->_respTrackData, $values)) {
            $where = $this->db->quoteInto('gr2t_id_respondent_track = ?', $this->_respTrackId);

            if (Gems_Tracker::$verbose) {
                $echo = '';
                foreach ($values as $key => $val) {
                    $echo .= $key . ': ' . $this->_respTrackData[$key] . ' => ' . $val . "\n";
                }
                MUtil_Echo::r($echo, 'Updated values for ' . $this->_respTrackId);
            }

            if (! isset($values['gr2t_changed'])) {
                $values['gr2t_changed'] = new MUtil_Db_Expr_CurrentTimestamp();
            }
            if (! isset($values['gr2t_changed_by'])) {
                $values['gr2t_changed_by'] = $userId;
            }

            $this->_respTrackData = $values + $this->_respTrackData;
            // MUtil_Echo::track($values);
            // return 1;
            return $this->db->update('gems__respondent2track', $values, $where);

        } else {
            return 0;
        }
    }

    /**
     * Add a one-off survey to the existing track.
     *
     * @param type $surveyId    the gsu_id of the survey to add
     * @param type $surveyData
     * @return Gems_Tracker_Token
     */
    public function addSurveyToTrack($surveyId, $surveyData, $userId)
    {
        //Do something to get a token and add it
        $tokenLibrary = $this->tracker->getTokenLibrary();

        //Now make sure the data to add is correct:
        $surveyData['gto_id_respondent_track'] = $this->_respTrackId;
        $surveyData['gto_id_organization']     = $this->_respTrackData['gr2t_id_organization'];
        $surveyData['gto_id_track']            = $this->_respTrackData['gr2t_id_track'];
        $surveyData['gto_id_respondent']       = $this->_respTrackData['gr2t_id_user'];
        $surveyData['gto_id_survey']           = $surveyId;

        $tokenId = $tokenLibrary->createToken($surveyData, $userId);

        //Now refresh the track to include the survey we just added (easiest way as order may change)
        $this->getTokens(true);

        // Update the track counter
        $this->_checkTrackCount($userId);

        return $this->_tokens[$tokenId];
    }

    /**
     * Add a one-off survey to the existing track.
     *
     * @param type $surveyId    the gsu_id of the survey to add
     * @param type $surveyData
     * @return Gems_Tracker_Token
     */
    public function addTokenToTrack(Gems_Tracker_Token $token, $tokenData, $userId)
    {
        //Now make sure the data to add is correct:
        $tokenData['gto_id_respondent_track'] = $this->_respTrackId;
        $tokenData['gto_id_organization']     = $this->_respTrackData['gr2t_id_organization'];
        $tokenData['gto_id_track']            = $this->_respTrackData['gr2t_id_track'];
        $tokenData['gto_id_respondent']       = $this->_respTrackData['gr2t_id_user'];
        $tokenData['gto_changed']             = new MUtil_Db_Expr_CurrentTimestamp();
        $tokenData['gto_changed_by']          = $userId;

        $where = $this->db->quoteInto('gto_id_token = ?', $token->getTokenId());
        $this->db->update('gems__tokens', $tokenData, $where);

        $token->refresh();

        //Now refresh the track to include the survey we just added (easiest way as order may change)
        $this->getTokens(true);

        // Update the track counter
        $this->_checkTrackCount($userId);

        return $token;
    }

    /**
     * Set menu parameters from this token
     *
     * @param Gems_Menu_ParameterSource $source
     * @return Gems_Tracker_RespondentTrack (continuation pattern)
     */
    public function applyToMenuSource(Gems_Menu_ParameterSource $source)
    {
        $source->setRespondentTrackId($this->_respTrackId);
        $source->setPatient($this->getPatientNumber(), $this->getOrganizationId());
        $source->setTrackId($this->getTrackId());
        $source->setTrackType($this->getTrackEngine()->getTrackType());
        $source->offsetSet('can_edit', $this->hasSuccesCode());

        return $this;
    }

    /**
     * Calculates the track end date
     *
     * The end date can be calculated when:
     *  - all active tokens have a completion date
     *  - or all active tokens have a valid until date
     *  - or the end date of the tokens is calculated using the end date
     *
     *  You can overrule this calculation at the project level.
     *
     * @return string or null
     */
    public function calculateEndDate()
    {
        // Exclude the tokens whose end date is calculated from the track end date
        $excludeWheres[] = sprintf(
                "gro_valid_for_source = '%s' AND gro_valid_for_field = 'gr2t_end_date'",
                Gems_Tracker_Engine_StepEngineAbstract::RESPONDENT_TRACK_TABLE
                );

        // Exclude the tokens whose start date is calculated from the track end date, while the
        // end date is calculated using that same start date
        $excludeWheres[] = sprintf(
                "gro_valid_after_source = '%s' AND gro_valid_after_field = 'gr2t_end_date' AND
                    gro_id_round = gro_valid_for_id AND
                    gro_valid_for_source = '%s' AND gro_valid_for_field = 'gto_valid_from'",
                Gems_Tracker_Engine_StepEngineAbstract::RESPONDENT_TRACK_TABLE,
                Gems_Tracker_Engine_StepEngineAbstract::TOKEN_TABLE
                );
        // In future we may want to add some nesting to this, e.g. tokens with an end date calculated
        // from another token whose... for the time being users should use the end date directly in
        // each token, otherwise the end date will not be calculated

        $maxExpression = "
            CASE
            WHEN SUM(
                CASE WHEN COALESCE(gto_completion_time, gto_valid_until) IS NULL THEN 1 ELSE 0 END
                ) > 0
            THEN NULL
            ELSE MAX(COALESCE(gto_completion_time, gto_valid_until))
            END as enddate";

        $tokenSelect = $this->tracker->getTokenSelect(array($maxExpression));
        $tokenSelect->andReceptionCodes(array())
                ->andRounds(array())
                ->forRespondentTrack($this->_respTrackId)
                ->onlySucces();

        foreach ($excludeWheres as $where) {
            $tokenSelect->forWhere('NOT (' . $where . ')');
        }

        $endDate = $tokenSelect->fetchOne();

        // MUtil_Echo::track($endDate, $tokenSelect->getSelect()->__toString());

        return $endDate;
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if ($this->db && (! $this->_respTrackData)) {
            $this->refresh();
        }

        return (boolean) $this->_respTrackData;
    }

    /**
     * Check this respondent track for changes to the tokens
     *
     * @param int $userId Id of the user who takes the action (for logging)
     * @param Gems_Tracker_Token $fromToken Optional token to start from
     * @param Gems_Tracker_Token $skipToken Optional token to skip in the recalculation when $fromToken is used
     * @return int The number of tokens changed by this code
     */
    public function checkTrackTokens($userId, Gems_Tracker_Token $fromToken = null, Gems_Tracker_Token $skipToken = null)
    {
        // Execute any defined functions
        $count = $this->handleTrackCalculation($userId);

        // Update token completion count.
        $this->_checkTrackCount($userId);

        $engine = $this->getTrackEngine();

        // Check for validFrom and validUntil dates that have changed.
        if ($fromToken) {
            return $count + $engine->checkTokensFrom($this, $fromToken, $userId, $skipToken);
        } elseif ($this->_checkStart) {
            return $count + $engine->checkTokensFrom($this, $this->_checkStart, $userId);
        } else {
            return $count + $engine->checkTokensFromStart($this, $userId);
        }
    }

    /**
     * Returns a token with a success reception code for this round or null
     *
     * @param type $roundId Gems round id
     * @param Gems_Tracker_Token $token
     * @return Gems_Tracker_Token
     */
    public function getActiveRoundToken($roundId, Gems_Tracker_Token $token = null)
    {
        if ((null !== $token) && $token->hasSuccesCode()) {
            // Cache the token
            //
            // WARNING: This may cause bugs for tracks where two tokens exists
            // with this roundId and a success reception code, but this does speed
            // this function witrh track engines where that should not occur.
            $this->_activeTokens[$token->getRoundId()] = $token;
        }

        // Nothing to find
        if (! $roundId) {
            return null;
        }

        // Use array_key_exists since there may not be a valid round
        if (! array_key_exists($roundId, $this->_activeTokens)) {
            $tokenSelect = $this->tracker->getTokenSelect();
            $tokenSelect->andReceptionCodes()
                    ->forRespondentTrack($this->_respTrackId)
                    ->forRound($roundId)
                    ->onlySucces();

            // MUtil_Echo::track($tokenSelect->__toString());

            if ($tokenData = $tokenSelect->fetchRow()) {
                $this->_activeTokens[$roundId] = $this->tracker->getToken($tokenData);
            } else {
                $this->_activeTokens[$roundId] = null;
            }
        }

        return $this->_activeTokens[$roundId];
    }

    /**
     *
     * @return string Internal code of the track
     */
    public function getCode()
    {
        static $track = false;

        if (!$track) {
            $track = $this->tracker->getTrackModel()->loadFirst(array('gtr_id_track'=>$this->_respTrackData['gr2t_id_track']));
        }

        if (is_array($track)) {
            return $track['gtr_code'];
        } else {
            return false;
        }
    }

    /**
     * Return all possible code fields with the values filled for those that exist for this track,
     * optionally with a prefix
     *
     * @return array [prefix]code => value
     */
    public function getCodeFields()
    {
        $codes   = $this->tracker->getAllCodeFields();
        $results = array_fill_keys($codes, null);

        $this->_ensureFieldData();
        if ($this->_fieldData) {
            foreach ($this->_fieldData as $id => $value) {
                if (isset($codes[$id])) {
                    $results[$codes[$id]] = $value;
                }
            }
        }

        return $results;
    }

    /**
     *
     * @return string Comment field
     */
    public function getComment()
    {
        if (isset($this->_respTrackData['gr2t_comment'])) {
            return $this->_respTrackData['gr2t_comment'];
        }

        return null;
    }

    /**
     * The round description of the first round that has not been answered.
     *
     * @return string Round description or Stopped/Completed if not found.
     */
    public function getCurrentRound()
    {
        $isStop = false;
        $today  = new Zend_Date();
        $tokens = $this->getTokens();
        $stop   = $this->util->getReceptionCodeLibrary()->getStopString();

        foreach ($tokens as $token) {
            $validUntil = $token->getValidUntil();

            if (! empty($validUntil) && $validUntil->isEarlier($today)) {
                continue;
            }

            if ($token->isCompleted()) {
                continue;
            }

            $code = $token->getReceptionCode();
            if (! $code->isSuccess()) {
                if ($code->getCode() === $stop) {
                    $isStop = true;
                }
                continue;
            }

            return $token->getRoundDescription();
        }
        if ($isStop) {
            return $this->translate->_('Track stopped');
        }

        return $this->translate->_('Track completed');
    }

    /**
     *
     * @param string $fieldName
     * @return MUtil_Date
     */
    public function getDate($fieldName)
    {
        if (isset($this->_respTrackData[$fieldName])) {
            $date = $this->_respTrackData[$fieldName];
        } else {
            $this->_ensureFieldData();

            if (isset($this->_fieldData[$fieldName])) {
                $date   = $this->_fieldData[$fieldName];
                $needle = Gems_Tracker_Model_FieldMaintenanceModel::APPOINTMENTS_NAME .
                        Gems_Tracker_Engine_TrackEngineAbstract::FIELD_KEY_SEPARATOR;

                if (MUtil_String::startsWith($fieldName, $needle)) {
                    $appointment = $this->tracker->getAppointment($date);
                    if ($appointment->isActive()) {
                        $date = $appointment->getAdmissionTime();
                    } else {
                        $date = false;
                    }
                }
            } else {
                $date = false;
            }
        }

        if ($date) {
            if ($date instanceof MUtil_Date) {
                return $date;
            }

            if (Zend_Date::isDate($date, Gems_Tracker::DB_DATETIME_FORMAT)) {
                return new MUtil_Date($date, Gems_Tracker::DB_DATETIME_FORMAT);
            }
            if (Zend_Date::isDate($date, Gems_Tracker::DB_DATE_FORMAT)) {
                return new MUtil_Date($date, Gems_Tracker::DB_DATE_FORMAT);
            }
            if (Gems_Tracker::$verbose)  {
                MUtil_Echo::r($date, 'Missed track date value:');
            }
        }
    }

    /**
     *
     * @return array of snippet names for deleting the track
     */
    public function getDeleteSnippets()
    {
        return $this->getTrackEngine()->getTrackDeleteSnippetNames($this);
    }

    /**
     *
     * @return array of snippet names for editing this respondent track
     */
    public function getEditSnippets()
    {
        return $this->getTrackEngine()->getTrackEditSnippetNames($this);
    }

    /**
     * Returns the field data for this respondent track id.
     *
     * @return array of the existing field values for this respondent track
     */
    public function getFieldData()
    {
        $this->_ensureFieldData();

        return $this->_fieldData;
    }

    /**
     * Returns the description of this track as stored in the fields.
     *
     * @return string
     */
    public function getFieldsInfo()
    {
        return $this->_respTrackData['gr2t_track_info'];
    }

    /**
     * Returns the first token in this track
     *
     * @return Gems_Tracker_Token
     */
    public function getFirstToken()
    {
        if (! $this->_firstToken) {
            if (! $this->_tokens) {
                //No cache yet, but we might need all tokens later
                $this->getTokens();
            }
            $this->_firstToken = reset($this->_tokens);
        }

        return $this->_firstToken;
    }

    /**
     * Returns the first token in this track
     *
     * @return Gems_Tracker_Token
     */
    public function getLastToken()
    {
        if (! $this->_tokens) {
            //No cache yet, but we might need all tokens later
            $this->getTokens();
        }
        return end($this->_tokens);
    }

    /**
     *
     * @return string The respondents patient number
     */
    public function getPatientNumber()
    {
        if (! isset($this->_respTrackData['gr2o_patient_nr'])) {
            $this->_ensureRespondentData();
        }

        return $this->_respTrackData['gr2o_patient_nr'];
    }

    /**
     *
     * @return int The organization id
     */
    public function getOrganizationId()
    {
        return $this->_respTrackData['gr2t_id_organization'];
    }

    /**
     * Return the Gems_Util_ReceptionCode object
     *
     * @return Gems_Util_ReceptionCode reception code
     */
    public function getReceptionCode()
    {
        return $this->util->getReceptionCode($this->_respTrackData['gr2t_reception_code']);
    }

    /**
     *
     * @return int The respondent id
     */
    public function getRespondentId()
    {
        return $this->_respTrackData['gr2t_id_user'];
    }

    /**
     * Return the default language for the respondent
     *
     * @return string Two letter language code
     */
    public function getRespondentLanguage()
    {
        if (! isset($this->_respTrackData['grs_iso_lang'])) {
            $this->_ensureRespondentData();

            if (! isset($this->_respTrackData['grs_iso_lang'])) {
                // Still not set in a project? The it is single language
                $this->_respTrackData['grs_iso_lang'] = $this->locale->getLanguage();
            }
        }

        return $this->_respTrackData['grs_iso_lang'];
    }

    /**
     * Return the name of the respondent
     *
     * @return string The respondents name
     */
    public function getRespondentName()
    {
        if (! isset($this->_respTrackData['grs_first_name'], $this->_respTrackData['grs_last_name'])) {
            $this->_ensureRespondentData();
        }

        return trim($this->_respTrackData['grs_first_name'] . ' ' . $this->_respTrackData['grs_surname_prefix']) . ' ' . $this->_respTrackData['grs_last_name'];
    }

    /**
     *
     * @return int The respondent2track id
     */
    public function getRespondentTrackId()
    {
        return $this->_respTrackId;
    }

    /**
     * The start date of this track
     *
     * @return MUtil_Date
     */
    public function getStartDate()
    {
        if (isset($this->_respTrackData['gr2t_start_date'])) {
            return new MUtil_Date($this->_respTrackData['gr2t_start_date'], Gems_Tracker::DB_DATETIME_FORMAT);
        }
    }

    /**
     * Returns all the tokens in this track
     *
     * @param boolean $refresh When true, always reload
     * @return array of Gems_Tracker_Token
     */
    public function getTokens($refresh = false)
    {
        if (! $this->_tokens || $refresh) {
            if ($refresh) {
                $this->_firstToken = null;
            }
            $this->_tokens       = array();
            $this->_activeTokens = array();
            $tokenSelect = $this->tracker->getTokenSelect();
            $tokenSelect->andReceptionCodes()
                ->forRespondentTrack($this->_respTrackId);

            $tokenRows = $tokenSelect->fetchAll();
            $prevToken = null;
            foreach ($tokenRows as $tokenData) {

                $token = $this->tracker->getToken($tokenData);

                $this->_tokens[$token->getTokenId()] = $token;

                // While we are busy, set this
                if ($token->hasSuccesCode()) {
                    $this->_activeTokens[$token->getRoundId()] = $token;
                }

                // Link the tokens
                if ($prevToken) {
                    $prevToken->setNextToken($token);
                }
                $prevToken = $token;
            }
        }

        return $this->_tokens;
    }

    /**
     *
     * @return Gems_Tracker_Engine_TrackEngineInterface
     */
    public function getTrackEngine()
    {
        return $this->tracker->getTrackEngine($this->_respTrackData['gr2t_id_track']);
    }

    /**
     *
     * @return int The track id
     */
    public function getTrackId()
    {
        return $this->_respTrackData['gr2t_id_track'];
    }

    /**
     *
     * @param mixed $token
     * @param int $userId The current user
     * @return int The number of tokens changed by this event
     */
    public function handleRoundCompletion($token, $userId)
    {
        if (! $token instanceof Gems_Tracker_Token) {
            $token = $this->tracker->getToken($token);
        }
        // MUtil_Echo::track($token->getRawAnswers());

        // Store the current token as startpoint if it is the first changed token
        if ($this->_checkStart) {
            if ($this->_checkStart->getRoundId() > $token->getRoundId()) {
                // Replace current token
                $this->_checkStart = $token;
            }
        } else {
            $this->_checkStart = $token;
        }

        // Process any events
        if ($event = $this->getTrackEngine()->getRoundChangedEvent($token->getRoundId())) {
            return $event->processChangedRound($token, $this, $userId);
        }

        return 0;
    }

    /**
     * Find out if there are track calculation events and delegate to the event if needed
     *
     * @param int $userId
     */
    public function handleTrackCalculation($userId)
    {
        // Process any events
        $trackEngine = $this->getTrackEngine();

        if ($event = $trackEngine->getTrackCalculationEvent()) {
            return $event->processTrackCalculation($this, $userId);
        }

        return 0;
    }

    /**
     * Find out if there are track completion events and delegate to the event if needed
     *
     * @param array $values The values changed before entering this event
     * @param int $userId
     */
    public function handleTrackCompletion(&$values, $userId)
    {
        // Process any events
        $trackEngine = $this->getTrackEngine();

        if ($event = $trackEngine->getTrackCompletionEvent()) {
            $event->processTrackCompletion($this, $values, $userId);
        }
    }

    /**
     *
     * @return boolean
     */
    public function hasSuccesCode()
    {
        if (! isset($this->_respTrackData['grc_success'])) {
            $this->_ensureReceptionCode();
        }

        return $this->_respTrackData['grc_success'];
    }

    /**
     *
     * @param array $gemsData Optional, the data refresh with, otherwise refresh from database.
     * @return Gems_Tracker_RespondentTrack (continuation pattern)
     */
    public function refresh(array $gemsData = null)
    {
        if (is_array($gemsData)) {
            $this->_respTrackData = $gemsData + $this->_respTrackData;
        } else {
            $sql  = "SELECT *
                        FROM gems__respondent2track INNER JOIN gems__reception_codes ON gr2t_reception_code = grc_id_reception_code
                        WHERE gr2t_id_respondent_track = ? LIMIT 1";

            $this->_respTrackData = $this->db->fetchRow($sql, $this->_respTrackId);
        }

        $this->_ensureFieldData(true);

        return $this;
    }

    /**
     * Set the end date for this respondent track.
     *
     * @param mixed $endDate The new end date for this track
     * @param int $userId The current user
     * @return int 1 if the token has changed, 0 otherwise
     */
    public function setEndDate($endDate, $userId)
    {
        $values['gr2t_end_date'] = $endDate;

        return $this->_updateTrack($values, $userId);
    }

    /**
     * Update one or more values for this track's fielddata.
     *
     * Return the complete set of fielddata
     *
     * @param array $data
     * @return array
     */
    public function setFieldData($data)
    {
        $engine    = $this->getTrackEngine();
        $fieldMap  = $engine->getFields();
        $fieldData = array();

        foreach ($data as $code => $value)
        {
            if ($index = array_search($code, $fieldMap)) {
                $fieldData[$index] = $value;
            }
        }
        $changeCount = $engine->setFieldsData($this->_respTrackId, $fieldData);
        if ($changeCount>0) {
            $this->_ensureFieldData(true);  // force reload
        }

        return $this->_fieldData;
    }

    /**
     * Set the reception code for this respondent track and make sure the
     * necessary cascade to the tokens and thus the source takes place.
     *
     * @param string $code The new (non-success) reception code or a Gems_Util_ReceptionCode object
     * @param string $comment Comment for tokens. False values leave value unchanged
     * @param int $userId The current user
     * @return int 1 if the token has changed, 0 otherwise
     */
    public function setReceptionCode($code, $comment, $userId)
    {
        // Make sure it is a Gems_Util_ReceptionCode object
        if (! $code instanceof Gems_Util_ReceptionCode) {
            $code = $this->util->getReceptionCode($code);
        }
        $changed = 0;

        // Apply this code both only when it is a track code.
        // Patient level codes are just cascaded to the tokens.
        //
        // The exception is of course when the exiting values must
        // be overwritten, e.g. when cooperation is retracted.
        if ($code->isForTracks() || $code->isOverwriter()) {
            $values['gr2t_reception_code'] = $code->getCode();
        }

        $values['gr2t_comment'] = $comment;

        $changed = $this->_updateTrack($values, $userId);

        if ($changed) {
            // Reload reception code values
            $this->_ensureReceptionCode($code->getAllData());
        }

        // Stopcodes have a different logic.
        if ($code->isStopCode()) {
            // Cascade stop to tokens
            foreach ($this->getTokens() as $token) {
                if ($token->hasSuccesCode() && (! $token->isCompleted())) {
                    $changed += $token->setReceptionCode($code, $comment, $userId);
                }
            }
            $changed = max($changed, 1);

            // Update token count / completion
            $this->_checkTrackCount($userId);

        } elseif (! $code->isSuccess()) {
            // Cascade code to tokens
            foreach ($this->getTokens() as $token) {
                if ($token->hasSuccesCode()) {
                    $token->setReceptionCode($code, $comment, $userId);
                }
            }
        }

        return $changed;
    }
}
