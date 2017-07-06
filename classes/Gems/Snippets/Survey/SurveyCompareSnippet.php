<?php

namespace Gems\Snippets\Survey;

class SurveyCompareSnippet extends \MUtil_Snippets_SnippetAbstract
{

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;
    /**
     * @var \Gems_Loader
     */
    public $loader;

    /**
     * @var \Zend_Locale
     */
    public $locale;

    /**
     * @var \Zend_Controller_Request_Abstract
     */
    public $request;

    /**
     * @var int Id of the source survey
     */
    protected $sourceSurveyId;

    /**
     * @var array list of question statusses used in the question compare functions and their table row classes
     */
    protected $questionStatusClasses = [
        'same' => 'success',
        'new' => 'info',
        'type-difference' => 'warning',
        'missing' => 'danger',
    ];

    /**
     * @var array List Survey ID => Survey name of available surveys. Initialized on load
     */
    protected $surveys;

    /**
     * @var int Id of the tartget Survey
     */
    protected $targetSurveyId;
    /**
     * @var \Gems_Util
     */
    public $util;

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
        parent::afterRegistry();

        $this->getSurveys();
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        if ($post = $this->request->getPost()) {
            $this->processForm($post);
        }

        $html = \MUtil_Html::create()->div(['id' => 'survey-compare']);

        if (isset($post['step'])
            && isset($post['source_survey']) && $post['source_survey'] == $this->sourceSurveyId
            && isset($post['target_survey']) && $post['target_survey'] == $this->targetSurveyId) {

            if ($post['step'] == 'update') {
                $html->append($this->getSurveyCompareTable($post));
            }
            if ($post['step'] == 'submit') {
                $html->append($this->getSurveyResults($post));
            }
            if ($post['step'] == 'confirmed') {
                $html->append($this->runUpdates($post));
            }
        } else {
            $html->append($this->getSurveyCompareTable($post));
        }

        return $html;
    }

    /**
     * Adds the survey compare form to the current table, showing the matches between different surveys
     *
     * @param $tableBody \MUtil_Html Table object
     * @param $post array List of Post data
     */
    public function addSurveyCompareForm($tableBody, $post)
    {
        if ($this->sourceSurveyId && $this->targetSurveyId) {
            $sourceSurveyData = $this->getSurveyData($this->sourceSurveyId);
            $targetSurveyData = $this->getSurveyData($this->targetSurveyId);
            $surveyCompare = $this->getSurveyCompare($sourceSurveyData, $targetSurveyData, $post);

            $icon = \MUtil_Html::create()->i(['class' => 'fa fa-exclamation-triangle', 'style' => 'color: #d43f3a; margin: 1em;', 'renderClosingTag' => true]);

            foreach($surveyCompare as $question) {
                $rowMessage = false;

                $statusClass = '';

                if (isset($this->questionStatusClasses[$question['status']])) {
                    $statusClass = $this->questionStatusClasses[$question['status']];
                }

                if ($question['status'] == 'type-difference') {
                    $rowMessage = $this->_('Question type is not the same. Check compatibility!');
                } elseif ($question['status'] == 'new') {
                    $rowMessage = $this->_('Question could not be found in source. Is this a new question?');
                } elseif ($question['status'] == 'missing') {
                    $rowMessage = $this->_('Warning! Question not found in target survey. Data will be lost on transfer');
                }

                $row = $tableBody->tr(['class' => $statusClass]);
                $row->td($question['target']);

                // Source column
                $row->td($this->getSurveyQuestionSelect($sourceSurveyData, $question['source'], $question['target']));

                // Target column
                if (isset($targetSurveyData[$question['target']])) {
                    $row->td($targetSurveyData[$question['target']]['question']);
                } else {
                    $row->td();
                }

                if ($rowMessage) {
                    $tableBody->tr(['class' => $statusClass])->td(['colspan' => 3])->append($icon, $rowMessage);
                }
            }
        }
    }

    /**
     * Adds the form elements for the source and target survey select to a table
     *
     * @param $tableBody \MUtil_Html table
     * @param $post array List of Post values
     */
    public function addSurveySelectForm($tableBody, $post)
    {
        $surveySelectRow = $tableBody->tr();
        $surveySelectRow->td();
        $surveySelectRow->td()->append($this->getSurveySelect('source_survey', $post));
        $surveySelectRow->td()->append($this->getSurveySelect('target_survey', $post));

        // Add update row
        $tableBody->tr()->td(['colspan' => 3])->button(['type' => 'submit', 'name' => 'step', 'value' => 'update'])->append($this->_('Compare'));
    }

    /**
     * Function to get Survey compare results sorted by status
     *
     * @param $post Post request data
     * @param $sourceSurveyData array with source survey data
     * @param $targetSurveyData array with target survey data
     * @return array compare results sorted by status
     */
    protected function getCategorizedResults($post, $sourceSurveyData, $targetSurveyData)
    {
        $surveyCompare = $this->getSurveyCompare($sourceSurveyData, $targetSurveyData, $post);

        $categorizedResults = [];
        foreach($this->questionStatusClasses as $status=>$class) {
            $categorizedResults[$status] = [];
        }

        foreach($surveyCompare as $result) {
            if (isset($result['status'])) {
                $categorizedResults[$result['status']][$result['target']] = $result;
                if ($result['status'] == 'missing') {
                    $categorizedResults[$result['status']][$result['source']] = $result;
                }
            } else {
                $categorizedResults['other'][] = $result;
            }
        }
        return $categorizedResults;
    }

    /**
     * Creates a table with comparison summary
     *
     * @param $post array List of post values
     * @param $sourceSurveyData array list of survey structure
     * @param $targetSurveyData
     * @return mixed
     */
    protected function getCompareResultSummary($post, $sourceSurveyData, $targetSurveyData)
    {
        $categorizedResults = $this->getCategorizedResults($post, $sourceSurveyData, $targetSurveyData);
        $table = \MUtil_Html::create()->table(['class' => 'browser table', 'style' => 'width: auto']);

        $row = $table->tr(['class' => $this->questionStatusClasses['new']]);
        $row->td(sprintf($this->_('%d new questions'), count($categorizedResults['new'])));
        $row->td(join(', ', array_keys($categorizedResults['new'])));

        $row = $table->tr(['class' => $this->questionStatusClasses['same']]);
        $row->td(sprintf($this->_('%d questions without warnings '), count($categorizedResults['same'])));
        $row->td(join(', ', array_keys($categorizedResults['same'])));

        $row = $table->tr(['class' => $this->questionStatusClasses['missing']]);
        $row->td(sprintf($this->_('%d missing questions'), count($categorizedResults['missing'])));
        $row->td(join(', ', array_keys($categorizedResults['missing'])));

        $row = $table->tr(['class' => $this->questionStatusClasses['type-difference']]);
        $row->td(sprintf($this->_('%d questions where the question type has changed'), count($categorizedResults['type-difference'])));
        $row->td(join(', ', array_keys($categorizedResults['type-difference'])));

        return $table;
    }



    /**
     * Get the complete comparison table
     *
     * @return \MUtil_Html Form nodes
     */
    public function getSurveyCompareTable($post)
    {
        $form = \MUtil_Html::create()->form(['method' => 'POST']);

        \MUtil_Echo::track($post);

        $table = $form->table(['class' => 'browser table']);

        $table->caption($this->getTitle());

        $headers = [
            $this->_('Question code'),
            $this->_('Source Survey'),
            $this->_('Target Survey'),
        ];

        $tableHeader = $table->thead()->tr();

        foreach($headers as $label) {
            $tableHeader->th($label);
        }

        $tableBody = $table->tbody();

        $this->addSurveySelectForm($tableBody, $post);

        if ($this->sourceSurveyId && $this->targetSurveyId) {
            $row = $tableBody->tr();
            $row->td($this->_('Usage'));
            $row->td($this->getSurveyStatistics($this->sourceSurveyId));
            $row->td($this->getSurveyStatistics($this->targetSurveyId));

            $this->addSurveyCompareForm($tableBody, $post);

            $form->input(['name' => 'source_survey', 'type' => 'hidden', 'value' => $this->sourceSurveyId]);
            $form->input(['name' => 'target_survey', 'type' => 'hidden', 'value' => $this->targetSurveyId]);
            $tableBody->tr()->td(['colspan' => 3])->button(['type' => 'submit', 'name' => 'step', 'value' => 'submit'])->append($this->_('Next'));
        }

        return $form;
    }

    /**
     * create an array with statusses of survey questions and how they're matched in the form
     *
     * @param $sourceSurveyData array with source survey data
     * @param $targetSurveyData array with target survey data
     * @param $post array List of POST data
     * @return array status array
     */
    public function getSurveyCompare($sourceSurveyData, $targetSurveyData, $post)
    {
        $surveyCompareArray = [];
        $missingSourceSurveyTitles = $sourceSurveyData;

        // show all questions that can be send to the target survey
        foreach ($targetSurveyData as $questionCode=> $questionData) {
            /*$currentSourceQuestionId = $questionData['id'];
            if (isset($post[$currentSourceQuestionId])) {
                $currentSourceQuestionId = $post[$currentQuestion];
            }*/

            $currentQuestionCode = $questionCode;
            if (isset($post['target']) && isset($post['target'][$currentQuestionCode])) {
                $currentQuestionCode = $post['target'][$currentQuestionCode];
            }

            $questionCompare = [
                'target' => $questionCode,
                'source' => $currentQuestionCode,
            ];
            $existingSourceQuestionTitle = null;

            if (isset($sourceSurveyData[$currentQuestionCode])) {
                if ($questionData['type'] === $sourceSurveyData[$currentQuestionCode]['type']) {
                    $questionCompare['status'] = 'same';
                } else {
                    $questionCompare['status'] = 'type-difference';
                }
                unset($missingSourceSurveyTitles[$currentQuestionCode]);
            } else {
                $questionCompare['status'] = 'new';
            }
            $surveyCompareArray[] = $questionCompare;
        }

        // show all questions that are missing in the target survey
        foreach($missingSourceSurveyTitles as $questionId=>$questionData) {
            $surveyCompareArray[] = [
                'target' => null,
                'source' => $questionId,
                'status' => 'missing',
            ];
        }

        return $surveyCompareArray;
    }

    /**
     * Creates a table with warnings about the survey answer transfer
     *
     * @return bool|\MUtil_Html Table with information
     */
    public function getComments()
    {
        $comments = false;
        $table = \MUtil_Html::create()->table(['class' => 'browser table', 'style' => 'width: auto']);

        $targetSurveyAnswers = 2453453; //$this->getNumberOfAnswers($this->targetSurveyId);
        if ($targetSurveyAnswers > 0) {
            $comments = true;
            $table
                ->tr(['class' => 'warning'])
                ->td(
                    sprintf(
                        $this->_('Target survey already has %d answers. Is this expected?'),
                        $targetSurveyAnswers
                    )
                );
        }

        if ($comments) {
            return $table;
        }

        return false;
    }

    /**
     * Creates a table showing the results of the survey compare
     *
     * @param $post array List of POST data
     * @return \MUtil_Html Table
     */
    public function getSurveyResults($post)
    {
        $sourceSurveyData = $this->getSurveyData($this->sourceSurveyId);
        $targetSurveyData = $this->getSurveyData($this->targetSurveyId);

        $surveys = $this->getSurveys();

        $surveyQuery = $this->whitespaceQuery(
            $this->buildSurveyQuery(
                $post,
                $sourceSurveyData,
                $targetSurveyData,
                true
            )
        );
        $tokenQuery = $this->whitespaceQuery($this->buildTokenQuery($post));

        $compareResultSummary = $this->getCompareResultSummary($post, $sourceSurveyData, $targetSurveyData);

        $comments = $this->getComments();

        $updateOptions = $this->getUpdateOptionsForm($post);

        $table = \MUtil_Html::create()->table(['class' => 'browser table']);
        $header = $table->thead()->tr();
        $header->th($surveys[$this->sourceSurveyId]);
        $header->th($surveys[$this->targetSurveyId]);

        $tableBody = $table->tbody();
        $row = $tableBody->tr();
        $row->td($this->getSurveyStatistics($this->sourceSurveyId));
        $row->td($this->getSurveyStatistics($this->targetSurveyId));
        $tableBody->tr()->td(['colspan' => 2]);

        $tableBody->tr()->th($this->_('Summary'), ['colspan' => 2]);
        $tableBody->tr()->td($compareResultSummary, ['colspan' => 2]);

        if ($comments) {
            $tableBody->tr()->th($this->_('Comments'), ['colspan' => 2]);
            $tableBody->tr()->td($comments, ['colspan' => 2]);
        }

        $tableBody->tr()->th($this->_('Options'), ['colspan' => 2]);
        $tableBody->tr()->th($updateOptions, ['colspan' => 2]);

        $tableBody->tr()->th($this->_('Survey Query'), ['colspan' => 2]);
        $tableBody->tr()->td(['colspan' => 2])->code()->pre($surveyQuery);

        $tableBody->tr()->th($this->_('Token Query'), ['colspan' => 2]);
        $tableBody->tr()->td(['colspan' => 2])->code()->pre($tokenQuery);

        return $table;
    }

    /**
     * Generates the lime_survey_xxx query from the selected survey links and the table structure
     *
     * @param $post array List of POST data
     * @param $sourceSurveyData array List of question information from the source survey
     * @param $targetSurveyData array List of question information from the target survey
     * @return string SQL query inserting question answers into the target survey
     */
    public function buildSurveyQuery($post, $sourceSurveyData, $targetSurveyData)
    {
        $sourceTableStructure = $this->getSurveyTableStructure($this->sourceSurveyId);
        $targetTableStructure = $this->getSurveyTableStructure($this->targetSurveyId);

        $targetSourceSurveyId = (string)$this->getSourceSurveyId($this->targetSurveyId);

        $targetTableColumns = [];
        $sourceTableColumns = [];
        if (isset($post['target'])) {
            foreach ($post['target'] as $targetColumn => $sourceColumn) {
                if (!empty($sourceColumn)) {
                    //\MUtil_Echo::track($sourceColumn, $targetSurveyData[$targetColumn], $sourceSurveyData[$sourceColumn]);

                    $targetTableColumns[] = $targetSurveyData[$targetColumn]['id'];
                    $sourceTableColumns[] = $sourceSurveyData[$sourceColumn]['id'];
                }
            }
        }
        $initColumns = [];
        foreach ($targetTableStructure as $columnName => $questionStructure) {
            if (strpos($columnName, $targetSourceSurveyId) !== 0) {
                $initColumns[$columnName] = $columnName;
            }
        }

        if (isset($initColumns['id'])) {
            unset($initColumns['id']);
        }

        //\MUtil_Echo::track($initColumns);

        $sourceFirstColumn = reset($sourceTableStructure);
        $sourceTable = $sourceFirstColumn['TABLE_NAME'];

        $targetFirstColumn = reset($targetTableStructure);
        $targetTable = $targetFirstColumn['TABLE_NAME'];

        $sql = "INSERT INTO {$targetTable} (" . join(', ', $initColumns) . ',' . join(', ', $targetTableColumns) . ")";
        $sql .= '(SELECT ' . join(', ', $initColumns) . ',' . join(', ', $sourceTableColumns) . " FROM " . $sourceTable . ')';

        return $sql;
    }

    /**
     * Generates a lime_tokens_xxx query from the table structure the source and target queries
     *
     * @return string SQL query inserting existing tokens into the target survey
     */
    public function buildTokenQuery()
    {
        $sourceTokenTableStructure = $this->getTokenTableStructure($this->sourceSurveyId);
        $targetTokenTableStructure = $this->getTokenTableStructure($this->targetSurveyId);

        $sourceFirstColumn = reset($sourceTokenTableStructure);
        $sourceTable = $sourceFirstColumn['TABLE_NAME'];

        $targetFirstColumn = reset($targetTokenTableStructure);
        $targetTable = $targetFirstColumn['TABLE_NAME'];

        $bothTokenTableStructures = [];
        foreach($targetTokenTableStructure as $columnName=>$columnData) {
            if (isset($sourceTokenTableStructure[$columnName])) {
                $bothTokenTableStructures[$columnName] = true;
            }
        }

        if (isset($bothTokenTableStructures['tid'])) {
            unset($bothTokenTableStructures['tid']);
        }

        $sql = "INSERT INTO {$targetTable} (".join(', ', array_keys($bothTokenTableStructures)).")";
        $sql .= "\n";
        $sql .= '(SELECT '.join(', ', array_keys($bothTokenTableStructures)).' FROM '.$sourceTable.')';

        return $sql;
    }

    /**
     * Get the survey ID in the survey source
     *
     * @param $surveyId int Gems survey ID
     * @return int source survey ID
     */
    public function getSourceSurveyId($surveyId)
    {
        $tracker = $this->loader->getTracker();
        $survey = $tracker->getSurvey($surveyId);

        return $survey->getSourceSurveyId();
    }

    /**
     * Get the table structure from a survey database
     *
     * @param $surveyId int Survey ID
     * @return array List of table structure
     */
    public function getSurveyTableStructure($surveyId)
    {
        $tracker = $this->loader->getTracker();
        $survey = $tracker->getSurvey($surveyId);
        $source = $survey->getSource();

        $structure = $source->getSurveyTableStructure($survey->getSourceSurveyId());

        return $structure;
    }

    /**
     * Creates a small html block of number of answers and usage in tracks of surveys
     *
     * @param $surveyId int id of the survey
     * @return \MUtil_Html_Sequence
     */
    public function getSurveyStatistics($surveyId)
    {
        $seq = new \MUtil_Html_Sequence();
        $seq->setGlue(\MUtil_Html::create('br'));
        $seq[] = $this->getNumberOfAnswers($surveyId);
        $seq[] = $this->calculateTrackUsage($surveyId);

        return $seq;
    }

    /**
     * Gets the number of answers in a survey
     *
     * @param $surveyId int Gems Survey ID
     * @return string translated string with number of answers
     */
    public function getNumberOfAnswers($surveyId)
    {
        $fields['tokenCount'] = 'COUNT(DISTINCT gto_id_token)';
        $select = $this->loader->getTracker()->getTokenSelect($fields);
        $select->forSurveyId($surveyId)
            ->onlyCompleted();
        $row = $select->fetchRow();
        return sprintf($this->_('Answered surveys: %d.'), $row['tokenCount']);
    }

    /**
     * Gets how many times a survey is used in tracks
     *
     * @param $surveyId int Gems Survey ID
     * @return array|\MUtil_Html_Sequence translated string with track usage
     */
    public function calculateTrackUsage($surveyId)
    {
        $select = $this->db->select();
        $select->from('gems__tracks', array('gtr_track_name'));
        $select->joinLeft('gems__rounds', 'gro_id_track = gtr_id_track', array('useCnt' => 'COUNT(*)'))
            ->where('gro_id_survey = ?', $surveyId)
            ->group('gtr_track_name');
        $usage = $this->db->fetchPairs($select);

        if ($usage) {
            $seq = new \MUtil_Html_Sequence();
            $seq->setGlue(\MUtil_Html::create('br'));
            foreach ($usage as $track => $count) {
                $seq[] = sprintf($this->plural(
                    'Used %d time in %s track.',
                    'Used %d times in %s track.',
                    $count), $count, $track);
            }
            return $seq;

        } else {
            return $this->_('Not used in any track.');
        }
    }

    /**
     * Gets question information about the survey structure from a specific survey and makes the result readable
     *
     * @param $surveyId int ID of the survey
     * @return array List of survey information
     */
    public function getSurveyData($surveyId)
    {
        $tracker = $this->loader->getTracker();

        $survey = $tracker->getSurvey($surveyId);

        $surveyInformation =  $survey->getQuestionInformation($this->locale);

        $filteredSurveyInformation = $surveyInformation;
        foreach($surveyInformation as $questionCode=>$questionInfo) {
            if ($questionInfo['class'] == 'question_sub') {
                $parentCode = $questionInfo['title'];
                $parent = $surveyInformation[$parentCode];
                $filteredSurveyInformation[$questionCode]['question'] = $parent['question'] . ' | ' . $questionInfo['question'];
                if (isset($filteredSurveyInformation[$parentCode])) {
                    unset($filteredSurveyInformation[$parentCode]);
                }
            }
        }

        return $filteredSurveyInformation;
    }

    /**
     * Get Survey name from Id
     *
     * @param $surveyId
     * @return string Survey name
     */
    public function getSurveyName($surveyId)
    {
        $tracker = $this->loader->getTracker();
        $survey = $tracker->getSurvey($surveyId);

        return $survey->getName();
    }

    /**
     * Get the form select with all the questions in the survey and the current selected one
     *
     * @param $surveyData array List of survey data
     * @param $currentQuestionCode string Current selected question code
     * @param $targetQuestionCode string the target question code used in the name field of the select
     * @return \MUtil_Html Select object
     */
    public function getSurveyQuestionSelect($surveyData, $currentQuestionCode, $targetQuestionCode)
    {
        $name = 'target['.$targetQuestionCode.']';
        if ($targetQuestionCode === null) {
            $name = 'notfound[]';
        }

        $select = \MUtil_Html::create()->select(['name' => $name]);

        $empty = $this->util->getTranslated()->getEmptyDropdownArray();
        $select->option(reset($empty), ['value' => '']);

        foreach($surveyData as $questionCode=>$questionData) {
            $attributes = ['value' => $questionCode];
            if ($currentQuestionCode === $questionCode) {
                $attributes['selected'] = 'selected';
            }

            $select->option($questionData['question'], $attributes);
        }
        return $select;
    }



    /**
     * Create a select element node with all available surveys
     *
     * @param $name string name of the survey select
     * @return mixed \MUtil_Html node
     */
    public function getSurveySelect($name, $post)
    {
        $surveys = $this->surveys;

        $select = \MUtil_Html::create()->select(['name' => $name]);

        $empty = $this->util->getTranslated()->getEmptyDropdownArray();
        $select->option(reset($empty), ['value' => '']);

        if (!empty($surveys)) {
            foreach ($surveys as $surveyId => $surveyName) {
                $attributes = ['value' => $surveyId];
                if (isset($post[$name]) && $post[$name] == $surveyId) {
                    $attributes['selected'] = 'selected';
                }
                $select->option($surveyName, $attributes);
            }
        }

        return $select;
    }


    /**
     * Get all available surveys
     *
     * @return array Survey Id => Survey name of available surveys
     */
    public function getSurveys()
    {
        if (!$this->surveys) {
            $dbLookup = $this->util->getDbLookup();

            $this->surveys = $dbLookup->getSurveys();
        }

        return $this->surveys;
    }

    /**
     * @return string Translated form title
     */
    public function getTitle()
    {
        return $this->_('Insert answers into a new version of a survey');
    }

    /**
     * Get the table structure of the survey token table
     *
     * @param $surveyId int Gens Survey ID
     * @return array List of token table structure
     */
    public function getTokenTableStructure($surveyId)
    {
        $tracker = $this->loader->getTracker();
        $survey = $tracker->getSurvey($surveyId);
        $source = $survey->getSource();

        $structure = $source->getTokenTableStructure($survey->getSourceSurveyId());

        return $structure;
    }

    /**
     * Creates a form with options which queries to run on the database
     *
     * @param $post array List of POST data
     * @return \Gems_Form Form with options
     */
    public function getUpdateOptionsForm($post)
    {
        $form = new \Gems_Form(['class' => 'form-horizontal']);

        $elements['trackReplace'] = $form->createElement('checkbox', 'track_replace',
            [
                'label' => $this->_('Replace in tracks'),
                'description' => $this->_('Replace all occurances of old survey in all tracks with the new survey'),
            ]
        );

        $elements['surveyQuery'] = $form->createElement('checkbox', 'survey_query',
            [
                'label' => $this->_('Copy survey answers'),
                'description' => $this->_('Copy all survey answers into the new survey in Limesurvey'),
            ]
        );

        $elements['tokenQuery'] = $form->createElement('checkbox', 'token_query',
            [
                'label' => $this->_('Copy tokens'),
                'description' => $this->_('Copy all tokens in Limesurvey to new survey'),
            ]
        );

        $elements['tokenUpdate'] = $form->createElement('checkbox', 'token_update',
            [
                'label' => $this->_('Update tokens'),
                'description' => $this->_('Update all existing gemstracker tokens to point to the new survey'),
            ]
        );

        $elements['submit'] = $form->createElement('submit', $this->_('Run queries'));

        $elements['step'] = $form->createElement('hidden', 'step',
            [
                'value' => 'confirmed',
            ]
        );

        $elements['sourceSurvey'] = $form->createElement('hidden', 'source_survey',
            [
                'value' => $this->sourceSurveyId,
            ]
        );

        $elements['targetSurvey'] = $form->createElement('hidden', 'target_survey',
            [
                'value' => $this->targetSurveyId,
            ]
        );

        foreach($post['target'] as $targetQuestion=>$sourceQuestion) {
            $elements[$targetQuestion] = $form->createElement('hidden', (string)$targetQuestion,
                [
                    'value' => $sourceQuestion,
                ]
            );
            $elements[$targetQuestion]->setBelongsTo('target');
        }


        $form->addElements($elements);

        return $form;
    }

    /**
     * process the form after a reload
     *
     * @param $post array POST Request data
     */
    protected function processForm($post)
    {
        if (isset($post['source_survey'])) {
            $this->sourceSurveyId = $post['source_survey'];
        }
        if (isset($post['target_survey'])) {
            $this->targetSurveyId = $post['target_survey'];
        }
    }

    /**
     * Run a query on the survey source database
     *
     * @param $surveyId int Gems Survey ID
     * @param $sql SQL statement to run on source database
     */
    protected function querySurveySource($surveyId, $sql)
    {
        $tracker = $this->loader->getTracker();
        $survey = $tracker->getSurvey($surveyId);
        $sourceSurveyId = $survey->getSourceSurveyId();
        $source = $survey->getSource();

        $source->lsDbQuery($sourceSurveyId, $sql);
    }


    /**
     * Runs the sql queries generated in the process
     *
     * @param $post array List of POST data
     * @return bool
     */
    protected function runUpdates($post)
    {
        $messages = [];

        $sourceSurveyData = $this->getSurveyData($this->sourceSurveyId);
        $targetSurveyData = $this->getSurveyData($this->targetSurveyId);

        if (isset($post['track_replace'])) {
            $this->setSurveysInTrack();

            $sourceSurveyName = $this->getSurveyName($this->sourceSurveyId);
            $targetSurveyName = $this->getSurveyName($this->targetSurveyId);

            $messages[] = sprintf(
                $this->_('All tracks have been updated to use \'%s\' instead of \'%s\''),
                $targetSurveyName,
                $sourceSurveyName
            );
        }

        if (isset($post['survey_query'])) {
            $this->setSurveyAnswersToNewSurvey($post, $sourceSurveyData, $targetSurveyData);

            $messages[] = sprintf(
                $this->_('All \'%s\' survey answers in limesurvey have been copied to \'%s\''),
                $sourceSurveyName,
                $targetSurveyName
            );
        }

        if (isset($post['token_query'])) {
            $this->setSurveyTokensToNewSurvey($post, $sourceSurveyData, $targetSurveyData);

            $messages[] = sprintf(
                $this->_('All \'%s\' tokens in limesurvey have been copied to \'%s\''),
                $sourceSurveyName,
                $targetSurveyName
            );
        }

        if (isset($post['token_update'])) {
            $this->setTokensToNewSurvey();

            $messages[] = sprintf(
                $this->_('All \'%s\' tokens in gemstracker have been updated to \'%s\''),
                $sourceSurveyName,
                $targetSurveyName
            );
        }

        $messenger = $this->getMessenger();

        foreach ($messages as $message) {
            $messenger->addMessage($message, 'success');
        }

        $url = [
            'controller' => 'update-survey',
            'action' => 'index',
        ];
        $router = new \Zend_Controller_Action_Helper_Redirector();
        $router->gotoRouteAndExit($url, null, $this->resetRoute);

        return false;
    }

    /**
     * Replaces the new survey in the Tracks it is being used
     */
    public function setSurveysInTrack()
    {
        $data = [
            'gro_id_survey' => $this->targetSurveyId,
        ];

        $where = [
            'gro_id_survey = ?' => $this->sourceSurveyId,
        ];

        $this->db->update('gems__rounds', $data, $where);
    }

    /**
     * Inserts survey answers from the source survey into the target survey
     *
     * @param $post array List of POST data
     * @param $sourceSurveyData array with source survey data
     * @param $targetSurveyData array with target survey data
     */
    public function setSurveyAnswersToNewSurvey($post, $sourceSurveyData, $targetSurveyData)
    {
        $sql = $this->buildSurveyQuery($post, $sourceSurveyData, $targetSurveyData);
        $this->querySurveySource($this->targetSurveyId, $sql);
    }

    /**
     * Inserts survey tokens from the source survey into the target survey
     *
     * @param $post array List of POST data
     * @param $sourceSurveyData array with source survey data
     * @param $targetSurveyData array with target survey data
     */
    public function setSurveyTokensToNewSurvey($post, $sourceSurveyData, $targetSurveyData)
    {
        $sql = $this->buildTokenQuery($post, $sourceSurveyData, $targetSurveyData);
        $this->querySurveySource($this->targetSurveyId, $sql);
    }

    /**
     * Updates existing tokens to use the new gems survey ID
     */
    public function setTokensToNewSurvey()
    {
        $data = [
            'gto_id_survey' => $this->targetSurveyId,
        ];

        $where = [
            'gto_id_survey = ?' => $this->sourceSurveyId,
        ];

        $this->db->update('gems__tokens', $data, $where);
    }

    /**
     * Adds whitespaces to an SQL query, so it'll look more readable on screen
     *
     * @param $sql string SQL statement
     * @return string SQL statement with white spaces
     */
    public function whitespaceQuery($sql)
    {
        return str_replace([', ', '(SELECT ', ' FROM '], [", \n", "\n(SELECT \n", "\n FROM "], $sql);
    }
}