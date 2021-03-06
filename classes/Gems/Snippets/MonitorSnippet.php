<?php

/**
 * @package    Gems
 * @subpackage Snippets
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

use MUtil\Util\MonitorJob;

/**
 * Snippet to display information about a specific monitor job
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.3
 */
class MonitorSnippet extends \MUtil_Snippets_SnippetAbstract
{
    public $caption;
    
    public $confirmParameter = 'delete';
    /**
     *
     * @var MonitorJob
     */
    public $monitorJob;
    
    /**
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;
    
    public $title;
    
    public function afterRegistry()
    {
        parent::afterRegistry();
        
        if (is_null($this->caption)) {
            $this->caption = $this->_(sprintf('Monitorjob %s', $this->monitorJob->getName()));
        }
        
        if (is_null($this->title)) {
            $this->title = $this->_('Monitorjob overview');
        }
    }

    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $seq = $this->getHtmlSequence();
        $seq->h3($this->title);
        
        if ($this->request->getParam($this->confirmParameter)) {
            $this->monitorJob->stop();
            // Now clear the job so it is empty
            $this->monitorJob = MonitorJob::getJob($this->monitorJob->getName());
        }

        $data = $this->getReadableData();
        if (empty($data)) {
            $seq[] = sprintf($this->_('No monitorjob found for %s'), $this->monitorJob->getName());
        } else {
            $tableContainer   = \MUtil_Html::create()->div(array('class' => 'table-container'));
            $table            = \MUtil_Html_TableElement::createArray($data, $this->caption);
            $table->class     = 'browser table';
            $tableContainer[] = $table;
            $seq[]            = $tableContainer;
            
            $seq->actionLink(array($this->confirmParameter => 1), $this->_('Delete'));
        }

        return $seq;
    }
    
    /**
     * Create readable output
     * 
     * @return array
     */
    protected function getReadableData()
    {
        $job  = $this->monitorJob;
        $data = $job->getArrayCopy();
        
        // Skip when job is not started
        if ($data['setTime'] == 0) return;
        
        $data['firstCheck'] = date(MonitorJob::$monitorDateFormat, $data['firstCheck']);
        $data['checkTime'] = date(MonitorJob::$monitorDateFormat, $data['checkTime']);
        $data['setTime'] = date(MonitorJob::$monitorDateFormat, $data['setTime']);
        $period = $data['period'];
        $mins = $period % 3600;
        $secs = $mins % 60;
        $hours = ($period - $mins) / 3600;
        $mins = ($mins - $secs) / 60;
        $data['period'] = sprintf('%2d:%02d:%02d',$hours,$mins,$secs);
        
        return $data;
    }


}