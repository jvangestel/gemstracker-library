<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TracksSnippet.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Snippets\Track;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 10, 2016 6:14:24 PM
 */
class TracksSnippet extends \Gems_Snippets_ModelTableSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array(
        'gr2t_start_date' => SORT_ASC,
        );

    /**
     * One of the \MUtil_Model_Bridge_BridgeAbstract MODE constants
     *
     * @var int
     */
    protected $bridgeMode = \MUtil_Model_Bridge_BridgeAbstract::MODE_ROWS;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * Menu actions to show in Edit box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public $menuEditActions = array('track' => 'edit-track');

    /**
     * Menu actions to show in Show box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public $menuShowActions = array('track' => 'show-track');

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = $this->loader->getTracker()->getRespondentTrackModel();

        $model->addColumn('CONCAT(gr2t_completed, \'' . $this->_(' of ') . '\', gr2t_count)', 'progress');

        $model->resetOrder();
        $model->set('gtr_track_name',    'label', $this->_('Track'));
        $model->set('gr2t_track_info',   'label', $this->_('Description'));
        $model->set('gr2t_start_date',   'label', $this->_('Start'),
            'formatFunction', $this->util->getTranslated()->formatDate,
            'default', \MUtil_Date::format(new \Zend_Date(), 'dd-MM-yyyy'));
        $model->set('gr2t_reception_code');
        $model->set('progress', 'label', $this->_('Progress')); // , 'tdClass', 'rightAlign', 'thClass', 'rightAlign');
        $model->set('assigned_by',       'label', $this->_('Assigned by'));

        return $model;
    }
}
