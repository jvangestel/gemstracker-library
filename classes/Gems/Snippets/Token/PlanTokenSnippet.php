<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Token;

/**
 * Snippet for showing the tokens for the applied filter over multiple respondents.
 *
 * @package    Gems
 * @subpackage Snippets\Token
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
        $br    = \MUtil_Html::create('br');

        // Add link to patient to overview
        $menuItems = $this->findMenuItems('respondent', 'show');
        if ($menuItems) {
            $menuItem = reset($menuItems);
            if ($menuItem instanceof \Gems_Menu_SubMenuItem) {
                $href = $menuItem->toHRefAttribute($bridge);

                if ($href) {
                    $aElem = new \MUtil_Html_AElement($href);
                    $aElem->setOnEmpty('');

                    // Make sure org is known
                    $model->get('gr2o_id_organization');

                    $model->set('gr2o_patient_nr', 'itemDisplay', $aElem);
                    $model->set('respondent_name', 'itemDisplay', $aElem);
                }
            }
        }

        $model->set('gto_id_token', 'formatFunction', 'strtoupper');

        $bridge->setDefaultRowClass(\MUtil_Html_TableElement::createAlternateRowClass('even', 'even', 'odd', 'odd'));
        $tr1 = $bridge->tr();
        $tr1->appendAttrib('class', $bridge->row_class);
        $tr1->appendAttrib('title', $bridge->gto_comment);

        $bridge->addColumn(
                $this->createInfoPlusCol($bridge),
                ' ')->rowspan = 2; // Space needed because TableElement does not look at rowspans
        $bridge->addSortable('gto_valid_from');
        $bridge->addSortable('gto_valid_until');

        $bridge->addSortable('gto_id_token');
        // $bridge->addSortable('gto_mail_sent_num', $this->_('Contact moments'))->rowspan = 2;

        $this->addRespondentCell($bridge, $model);
        $bridge->addMultiSort('ggp_name', [$this->createActionButtons($bridge)]);

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
                array($bridge->gtr_track_name->if(\MUtil_Html::raw(' &raquo; ')), ' '),
                'gsu_survey_name', 'gto_round_description');
        } else {
            $bridge->addMultiSort('gto_round_description', \MUtil_Html::raw('; '), 'gsu_survey_name');
        }
        $bridge->addSortable('assigned_by');
    }

    /**
     * As this is a common cell setting, this function allows you to overrule it.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addRespondentCell(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $bridge->addMultiSort('gr2o_patient_nr', \MUtil_Html::raw('; '), 'respondent_name');
    }

    /**
     * Return a list of possible action buttons for the token
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @return array of HtmlElements
     */
    public function createActionButtons(\MUtil_Model_Bridge_TableBridge $bridge)
    {
        $tData = $this->util->getTokenData();

        // Action links
        $actionLinks['ask']    = $tData->getTokenAskLinkForBridge($bridge, true);
        $actionLinks['email']  = $tData->getTokenEmailLinkForBridge($bridge);
        $actionLinks['answer'] = $tData->getTokenAnswerLinkForBridge($bridge);

        $output = [];
        foreach ($actionLinks as $key => $actionLink) {
            if ($actionLink) {
                $output[] = ' ';
                $output[$key] = \MUtil_Html::create(
                        'div',
                        $actionLink,
                        ['class' => 'rightFloat', 'renderWithoutContent' => false, 'style' => 'clear: right;']
                        );
            }
        }

        return $output;
    }

    /**
     * Returns a '+' token button
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @return \MUtil_Html_AElement
     */
    protected function createInfoPlusCol(\MUtil_Model_Bridge_TableBridge $bridge)
    {
        $tData = $this->util->getTokenData();

        return [
            'class' => 'text-right',
            $tData->getTokenStatusLinkForBridge($bridge),
            ' ',
            $tData->getTokenShowLinkForBridge($bridge, true)
            ];
    }

    /**
     * Returns a '+' token button
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @return \MUtil_Html_AElement
     */
    protected function createShowTokenButton(\MUtil_Model_Bridge_TableBridge $bridge)
    {
        $link = $this->util->getTokenData()->getTokenShowLinkForBridge($bridge, true);

        if ($link) {
            return $link;
        }
    }
}
