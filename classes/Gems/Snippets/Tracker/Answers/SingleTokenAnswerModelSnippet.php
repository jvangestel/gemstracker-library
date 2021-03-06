<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Displays answers from a single token to a survey.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.7
 */
class Gems_Snippets_Tracker_Answers_SingleTokenAnswerModelSnippet extends \Gems_Tracker_Snippets_AnswerModelSnippetGeneric
{
    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(\MUtil_Model_ModelAbstract $model)
    {
        if ($this->request) {
            $this->processSortOnly($model);

            $model->setFilter(array('gto_id_token' => $this->token->getTokenId()));
        }
    }
}
