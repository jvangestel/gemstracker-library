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
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: ComplianceTableSnippet.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Snippets_Tracker_Compliance_ComplianceTableSnippet extends Gems_Snippets_ModelTableSnippetGeneric
{
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
        $table = $bridge->getTable();
        $table->appendAttrib('class', 'compliance');

        $thead  = $table->thead();
        $th_row = $thead->tr(array('class' => 'rounds'));
        $th     = $th_row->td();
        $span   = 1;
        $cRound = null;
        $thead->tr();

        if ($showMenuItem = $this->getShowMenuItem()) {
            $bridge->addItemLink($showMenuItem->toActionLinkLower($this->request, $bridge));
        }

        foreach($model->getItemsOrdered() as $name) {
            if ($label = $model->get($name, 'label')) {
                $round = $model->get($name, 'round');
                if ($round == $cRound) {
                    $span++;
                    $class = null;
                } else {
                    $th->append($cRound);
                    $th->colspan = $span;

                    $span   = 1;
                    $cRound = $round;
                    $class = 'newRound';
                    $th     = $th_row->td(array('class' => $class));
                }

                if ($model->get($name, 'noSort')) {
                    $tds = $bridge->addColumn(
                            array(
                                $bridge->$name,
                                'class' => array('round', MUtil_Lazy::method($this, 'getClassFor', $bridge->$name)),
                                'title' => array(
                                    MUtil_Lazy::method($this, 'getDescriptionFor', $bridge->$name),
                                    "\n" . $model->get($name, 'description')
                                    ),
                                ),
                            array($label, 'title' => $model->get($name, 'description'), 'class' => 'round')
                            );
                } else {
                    $tds = $bridge->addSortable($name, $label);
                }
                if ($class) {
                    $tds->appendAttrib('class', $class);
                }
            }
        }
        $th->append($cRound);
        $th->colspan = $span;
    }

    /**
     * Returns the class to display the answer
     *
     * @param string $value Character
     * @return string
     */
    public function getClassFor($value)
    {
        switch ($value) {
            case 'A':
                return 'answered';
            case 'M':
                return 'missed';
            case 'O':
                return 'open';
            case 'U':
                return 'unknown';
            case 'W':
                return 'waiting';
            default:
                return 'empty';
        }
    }

    /**
     * Returns the decription to add to the answer
     *
     * @param string $value Character
     * @return string
     */
    public function getDescriptionFor($value)
    {
        switch ($value) {
            case 'A':
                return $this->_('Answered');
            case 'M':
                return $this->_('Missed deadline');
            case 'O':
                return $this->_('Open - can be answered now');
            case 'U':
                return $this->_('Valid from date unknown');
            case 'W':
                return $this->_('Valid from date in the future');
            default:
                return $this->_('Token does not exist');
        }
    }

    /**
     * Returns a show menu item, if access is allowed by privileges
     *
     * @return Gems_Menu_SubMenuItem
     */
    protected function getShowMenuItem()
    {
        return $this->findMenuItem('track', 'show-track');
    }
}
