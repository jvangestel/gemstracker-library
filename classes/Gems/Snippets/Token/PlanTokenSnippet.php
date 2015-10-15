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
 * @subpackage Snippets_Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

namespace Gems\Snippets\Token;

/**
 * Snippet for showing the all tokens for a single respondent.
 *
 * @package    Gems
 * @subpackage Snippets_Token
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class PlanTokenSnippet extends \Gems_Snippets_TokenModelSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array(
        'calc_used_date'  => SORT_ASC,
        'gtr_track_name'  => SORT_ASC,
        'gto_round_order' => SORT_ASC,
        'gto_created'     => SORT_ASC,
        );

    /**
     * Sets pagination on or off.
     *
     * @var boolean
     */
    public $browse = true;

    /**
     * The token model
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     *
     * @var boolean
     */
    protected $multiTracks = true;

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $HTML  = \MUtil_Html::create();

        $model->set('gto_id_token', 'formatFunction', 'strtoupper');

        $bridge->setDefaultRowClass(\MUtil_Html_TableElement::createAlternateRowClass('even', 'even', 'odd', 'odd'));
        $tr1 = $bridge->tr();
        $tr1->appendAttrib('class', $bridge->row_class);
        $tr1->appendAttrib('title', $bridge->gto_comment);

        $bridge->addColumn($this->createShowTokenButton($bridge), ' ')->rowspan = 2; // Space needed because TableElement does not look at rowspans
        $bridge->addSortable('gto_valid_from');
        $bridge->addSortable('gto_valid_until');

        $bridge->addSortable('gto_id_token');
        // $bridge->addSortable('gto_mail_sent_num', $this->_('Contact moments'))->rowspan = 2;

        $bridge->addMultiSort('gr2o_patient_nr', $HTML->raw('; '), 'respondent_name');
        $bridge->addMultiSort('ggp_name', array($this->createActionButtons($bridge)));

        $tr2 = $bridge->tr();
        $tr2->appendAttrib('class', $bridge->row_class);
        $tr2->appendAttrib('title', $bridge->gto_comment);
        $bridge->addSortable('gto_mail_sent_date');
        $bridge->addSortable('gto_completion_time');
        $bridge->addSortable('gto_mail_sent_num', $this->_('Contact moments'));

        if ($this->multiTracks) {
            $model->set('gr2t_track_info', 'tableDisplay', 'smallData');
            $model->set('gto_round_description', 'tableDisplay', 'smallData');
            $bridge->addMultiSort(
                'gtr_track_name', 'gr2t_track_info',
                array($bridge->gtr_track_name->if($HTML->raw(' &raquo; ')), ' '),
                'gsu_survey_name', 'gto_round_description');
        } else {
            $bridge->addMultiSort('gto_round_description', $HTML->raw('; '), 'gsu_survey_name');
        }
        $bridge->addSortable('assigned_by');
    }

    public function createActionButtons(\MUtil_Model_Bridge_TableBridge $bridge)
    {
        // Get the other token buttons
        $menuItems = $this->menu->findAll(
                array('controller' => 'track', 'action' => array('email', 'answer'), 'allowed' => true)
                );
        if ($menuItems) {
            $buttons = $menuItems->toActionLink($this->request, $bridge);
            $buttons->appendAttrib('class', 'rightFloat');
        } else {
            $buttons = null;
        }
        // Add the ask button
        $menuItem = $this->menu->find(array('controller' => 'ask', 'action' => 'take', 'allowed' => true));
        if ($menuItem) {
            $askLink = $menuItem->toActionLink($this->request, $bridge);
            $askLink->appendAttrib('class', 'rightFloat');

            if ($buttons) {
                // Show previous link if show, otherwise show ask link
                $buttons = array($buttons, $askLink);
            } else {
                $buttons = $askLink;
            }
        }

        return $buttons;
    }

    protected function createShowTokenButton(\MUtil_Model_Bridge_TableBridge $bridge)
    {
        // Get the token buttons
        $item = $this->menu->findAllowedController('track', 'show');
        if ($item) {
            $button = $item->toActionLink($this->request, $bridge, $this->_('+'));
            $button->title = $bridge->gto_id_token->strtoupper();

            return $button;
        }
    }
}
