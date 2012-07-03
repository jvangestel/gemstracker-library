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
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 215 2011-07-12 08:52:54Z michiel $
 */

/**
 * Handles export of all tracks/surveys for a respondent
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.5
 */
class Gems_Export_RespondentExport extends Gems_Registry_TargetAbstract
{
    public $_wkhtmltopdfLocation = "";

    protected $_reportFooter         = 'Export_ReportFooterSnippet';
    protected $_reportHeader         = 'Export_ReportHeaderSnippet';
    protected $_respondentSnippet    = 'Export_RespondentSnippet';

    protected $_groupedSurveySnippet = 'TrackAnswersModelSnippet';
    protected $_singleSurveySnippet  = 'AnswerModelSnippet';

    /**
     *
     * @var GemsEscort
     */
    public $escort;
    
    private $html;
    
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
    
    /**
     * @var Zend_Translate_Adapter
     */
    public $translate;

    /**
     *
     * @var Gems_Util
     */
    public $util;

    public $view;

    public function _($messageid, $locale = null)
    {
        return $this->translate->_($messageid, $locale);
    }

    public function afterRegistry()
    {
        parent::afterRegistry();

        // Load the pdf class from the project settings if available
        if (isset($this->project->export) && isset($this->project->export['wkhtmltopdf'])) {
            $this->_wkhtmltopdfLocation = $this->project->export['wkhtmltopdf'];
        }
    }

        /**
     * Calls 'wkhtmltopdf' to convert HTML to PDF
     *
     * @param  string $content The HTML source
     * @return string The converted PDF file
     * @throws Exception
     */
    protected function _convertToPdf($content)
    {
        $tempInputFilename  = GEMS_ROOT_DIR . '/var/tmp/export-' . md5(time() . rand()) . '.html';
        $tempOutputFilename = GEMS_ROOT_DIR . '/var/tmp/export-' . md5(time() . rand()) . '.pdf';

        file_put_contents($tempInputFilename, $content);

        if (!file_exists($tempInputFilename)) {
            throw new Exception("Unable to create temporary file '{$tempInputFilename}'");
        }

        $lastLine = exec(escapeshellarg($this->_wkhtmltopdfLocation) . ' ' . escapeshellarg($tempInputFilename)
            . ' ' . escapeshellarg($tempOutputFilename), $outputLines, $return);

        if ($return > 0) {
            @unlink($tempInputFilename);
            @unlink($tempOutputFilename);

            throw new Exception(sprintf($this->_('Unable to run PDF conversion: "%s"'), $lastLine));
        }

        $pdfContents = file_get_contents($tempOutputFilename);

        @unlink($tempInputFilename);
        @unlink($tempOutputFilename);

        return $pdfContents;
    }

    /**
     * Returns true when this token should be displayed
     *
     * @param Gems_Tracker_Token $token
     * @return boolean
     */
    public function _displayToken($token)
    {
        if ($token->isCompleted()) {
            return true;
        }

        return false;
    }

    /**
     * Determines if this particular token should be included
     * in the report
     *
     * @param  Gems_Tracker_Token $token
     * @return boolean This dummy implementation always returns true
     */
    protected function _isTokenInFilter(Gems_Tracker_Token $token)
    {
        // Only if token has a success code
        if ($token->getReceptionCode()->isSuccess()) {
            return true;
        }
        
        return false;
    }

    /**
     * Exports all the tokens of a single track, grouped by round
     *
     * @param Gems_Tracker_RespondentTrack $track
     */
    protected function _exportTrackTokens(Gems_Tracker_RespondentTrack $track)
    {
        $groupSurveys = $this->_group;
        $token        = $track->getFirstToken();
        $engine       = $track->getTrackEngine();
        $surveys      = array();

        $table = $this->html->table(array('class' => 'browser'));
        $table->th($this->_('Survey'))
              ->th($this->_('Round'))
              ->th($this->_('Token'))
              ->th($this->_('Status'));
        $this->html->br();

        while ($token) {
            //Should this token be in the list?
            if (!$this->_isTokenInFilter($token)) {
                $token = $token->getNextToken();
                continue;
            }

            $table->tr()->td($token->getSurveyName())
                        ->td(($engine->getTrackType() == 'T' ? $token->getRoundDescription() : $this->_('Single Survey')))
                        ->td(strtoupper($token->getTokenId()))
                        ->td($token->getStatus());

            //Should we display the answers?
            if (!$this->_displayToken($token)) {
                $token = $token->getNextToken();
                continue;
            }

            if ($engine->getTrackType() == 'S' || !$groupSurveys) {
                $this->html->span()->b($token->getSurveyName() . ($token->getRoundDescription() ? ' (' . $token->getRoundDescription() . ')' : ''));
                $this->html->snippet($this->_singleSurveySnippet, 'token', $token, 'tokenId', $token->getTokenId(),
                	'showHeaders', false, 'showButtons', false, 'showSelected', false, 'showTakeButton', false);

                $this->html->br();
            } else {
                if (!isset($surveys[$token->getSurveyId()])) {
                    $surveys[$token->getSurveyId()] = true;

                    $this->html->span()->b($token->getSurveyName());
                    $this->html->snippet($this->_groupedSurveySnippet, 'token', $token, 'tokenId', $token->getTokenId(),
                    	'showHeaders', false, 'showButtons', false, 'showSelected', false, 'showTakeButton', false);

                    $this->html->br();
                }
            }

            $token = $token->getNextToken();
        }
    }

    /**
     * Exports a single track
     *
     * @param Gems_Tracker_RespondentTrack $track
     */
    protected function _exportTrack(Gems_Tracker_RespondentTrack $track)
    {
        if ($track->getReceptionCode() != GemsEscort::RECEPTION_OK) {
            return;
        }

        $trackModel = $this->loader->getTracker()->getRespondentTrackModel();
        $trackModel->resetOrder();
        $trackModel->set('gtr_track_name',    'label', $this->_('Track'));
        $trackModel->set('gr2t_track_info',   'label', $this->_('Description'),
            'description', $this->_('Enter the particulars concerning the assignment to this respondent.'));
        $trackModel->set('assigned_by',       'label', $this->_('Assigned by'));
        $trackModel->set('gr2t_start_date',   'label', $this->_('Start'),
            'formatFunction', $this->util->getTranslated()->formatDate,
            'default', MUtil_Date::format(new Zend_Date(), 'dd-MM-yyyy'));
        $trackModel->set('gr2t_reception_code');
        $trackModel->set('gr2t_comment',       'label', $this->_('Comment'));
        $trackModel->setFilter(array('gr2t_id_respondent_track' => $track->getRespondentTrackId()));
        $trackData = $trackModel->loadFirst();

        $this->html->h3($this->_('Track') . ' ' . $trackData['gtr_track_name']);

        $bridge = new MUtil_Model_VerticalTableBridge($trackModel, array('class' => 'browser'));
        $bridge->setRepeater(MUtil_Lazy::repeat(array($trackData)));
        $bridge->th($this->_('Track information'), array('colspan' => 2));
        $bridge->setColumnCount(1);
        foreach($trackModel->getItemsOrdered() as $name) {
            if ($label = $trackModel->get($name, 'label')) {
                $bridge->addItem($name, $label);
            }
        }

        $table = $bridge->getTable();

        foreach ($track->getFieldData() as $field => $value) {
            if (is_int($field)) {
                continue;
            }

            $table->tr()->th($field)->td($value);
        }

        $this->html[] = $table;
        $this->html->br();

        $this->_exportTrackTokens($track);

        $this->html->hr();
    }

    /**
     * Exports a single respondent
     *
     * @param string $respondentId
     */
    protected function _exportRespondent($respondentId)
    {
        $respondentModel = $this->loader->getModels()->getRespondentModel(false);
        //Insert orgId when set
        $respondentModel->applyRequest(Zend_Controller_Front::getInstance()->getRequest());
        $respondentModel->addFilter(array('gr2o_patient_nr' => $respondentId));
        $respondentData = $respondentModel->loadFirst();

        $this->html->snippet($this->_respondentSnippet,
            'model', $respondentModel,
            'data', $respondentData,
            'respondentId', $respondentId);

        $tracker = $this->loader->getTracker();
        $tracks = $tracker->getRespondentTracks($respondentData['gr2o_id_user'], $respondentData['gr2o_id_organization']);

        foreach ($tracks as $trackId => $track) {
            $this->_exportTrack($track);
        }
    }

    /**
     * Renders the entire report (including layout)
     *
     * @param array|string[] $respondentId
     * @param boolean $group Group same surveys or not
     * @param string $format html|pdf, the output format to use
     */
    public function render($respondents, $group = true, $format = 'html')
    {
        $this->_group = $group;
        $this->_format = $format;
        $this->html = new MUtil_Html_Sequence();

        $this->html->snippet($this->_reportHeader);

        $respondentCount = count($respondents);
        $respondentIdx   = 0;
        foreach ($respondents as $respondentId) {
            $respondentIdx++;
            $this->_exportRespondent($respondentId);

            if ($respondentIdx < $respondentCount) {
                // Add some whitespace between patients
                $this->html->div('', array('style' => 'height: 100px'));
            }
        }

        $this->html->snippet($this->_reportFooter,  'respondents', $respondents);

        $this->escort->menu->setVisible(false);
        if ($this->escort instanceof Gems_Project_Layout_MultiLayoutInterface) {
            $this->escort->layoutSwitch();
        }
        $this->escort->postDispatch(Zend_Controller_Front::getInstance()->getRequest());

        Zend_Controller_Action_HelperBroker::getExistingHelper('layout')->disableLayout();
        Zend_Controller_Action_HelperBroker::getExistingHelper('viewRenderer')->setNoRender(true);

        $this->view->layout()->content = $this->html->render($this->view);

        $content = $this->view->layout->render();

        if ($this->_format == 'pdf') {
            $content = $this->_convertToPdf($content);
            $filename = 'respondent-export-' . strtolower($respondentId) . '.pdf';

			header('Content-Type: application/x-download');
            header('Content-Length: '.strlen($content));
            header('Content-Disposition: inline; filename="'.$filename.'"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
        }

        echo $content;

        $this->escort->menu->setVisible(true);
    }

}