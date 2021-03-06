<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\ReceptionCode
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ChangeReceptionCodeSnippetAbstract.php 2493 2015-04-15 16:29:48Z matijsdejong $
 */

namespace Gems\Snippets\ReceptionCode;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\ReceptionCode
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 7-mei-2015 11:17:41
 */
abstract class ChangeReceptionCodeSnippetAbstract extends \Gems_Snippets_ModelFormSnippetAbstract
{
    /**
     * Array of items that should be shown to the user
     *
     * @var array
     */
    protected $editItems = array();

    /**
     * Array of items that should be shown to the user
     *
     * @var array
     */
    protected $exhibitItems = array();

    /**
     * When a fixed reception code is specified, then no choice list is presented to the user
     *
     * @var string
     */
    protected $fixedReceptionCode = null;

    /**
     * Array of items that should be kept, but as hidden
     *
     * @var array
     */
    protected $hiddenItems = array();

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * The item containing the reception code field
     *
     * @var string
     */
    protected $receptionCodeItem = '';

    /**
     * The name of the action to forward to after form completion
     *
     * @var string
     */
    protected $routeAction = 'show';

    /**
     * Marker that the snippet is in undelete mode (for subclasses)
     *
     * @var boolean
     */
    protected $unDelete = false;

    /**
     * Optional right to check for undeleting
     *
     * @var string
     */
    protected $unDeleteRight;

    /**
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Called after loadFormData() and isUndeleting() but before the form is created
     *
     * @return array
     */
    abstract public function getReceptionCodes();

    /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        if ($this->formTitle) {
            return $this->formTitle;
        } elseif ($this->unDelete) {
            return sprintf($this->_('Undelete %s!'), $this->getTopic());
        } else {
            return sprintf($this->_('Delete %s!'), $this->getTopic());
        }
    }

    /**
     * Called after loadFormData() in loadForm() before the form is created
     *
     * @return boolean Are we undeleting or deleting?
     */
    abstract public function isUndeleting();

    /**
     * Initialize the _items variable to hold all items from the model
     */
    protected function initItems()
    {
        if (is_null($this->_items)) {
            // Set the element classes
            $model = $this->getModel();
            $keys  = $model->getKeys();

            foreach ($model->getItemNames() as $item) {
                if (($item == $this->receptionCodeItem) || in_array($item, $this->editItems)) {
                    continue;
                }
                if (in_array($item, $this->exhibitItems)) {
                    $model->set($item, 'elementClass', 'Exhibitor');
                } elseif (in_array($item, $this->hiddenItems)) {
                    $model->set($item, 'elementClass', 'Hidden');
                } elseif (in_array($item, $keys)) {
                    $model->set($item, 'elementClass', 'Hidden');
                } else {
                    $model->set($item, 'elementClass', 'None');
                }
            }

            $this->_items = array_merge(
                    $this->hiddenItems,
                    $this->exhibitItems,
                    array($this->receptionCodeItem),
                    $this->editItems
                    );
        }
    }

    /**
     * Makes sure there is a form.
     */
    protected function loadForm()
    {
        $model = $this->getModel();

        $this->unDelete = $this->isUndeleting();
        $receptionCodes = $this->getReceptionCodes();
        // \MUtil_Echo::track($this->unDelete, $receptionCodes);

        if (! $receptionCodes) {
            throw new \Gems_Exception($this->_('No reception codes exist.'));
        }

        if ($this->unDelete) {
            $label = $this->_('Restore code');
        } else {
            if ($this->unDeleteRight && (! $this->loader->getCurrentUser()->hasPrivilege($this->unDeleteRight))) {
                $this->addMessage($this->_('Watch out! You yourself cannot undo this change!'));
            }
            $label = $this->_('Rejection code');
        }
        $model->set($this->receptionCodeItem, 'label', $label);

        if ($this->fixedReceptionCode) {
            if (! isset($receptionCodes[$this->fixedReceptionCode])) {
                if ($this->fixedReceptionCode == $this->formData[$this->receptionCodeItem]) {
                    throw new \Gems_Exception($this->_('Already set to this reception code.'));
                } else {
                    throw new \Gems_Exception(sprintf(
                            $this->_('Reception code %s does not exist.'),
                            $this->fixedReceptionCode
                            ));
                }
            }
        } elseif (count($receptionCodes) == 1) {
            reset($receptionCodes);
            $this->fixedReceptionCode = key($receptionCodes);
        }

        if ($this->fixedReceptionCode) {
            $model->set($this->receptionCodeItem,
                    'elementClass', 'Exhibitor',
                    'multiOptions', $receptionCodes
                    );
            $this->formData[$this->receptionCodeItem] = $this->fixedReceptionCode;

        } else {
            $model->set($this->receptionCodeItem,
                    'elementClass', 'Select',
                    'multiOptions', array('' => '') + $receptionCodes,
                    'required', true,
                    'size', min(7, max(3, count($receptionCodes) + 2))
                    );

            if (! isset($this->formData[$this->receptionCodeItem], $receptionCodes[$this->formData[$this->receptionCodeItem]])) {
                $this->formData[$this->receptionCodeItem] = '';
            }
        }

        $this->saveLabel = $this->getTitle();

        parent::loadForm();
    }

    /**
     * Hook containing the actual save code.
     *
     * Call's afterSave() for user interaction.
     *
     * @see afterSave()
     */
    protected function saveData()
    {
        $this->beforeSave();

        $changed = $this->setReceptionCode(
                $this->formData[$this->receptionCodeItem],
                $this->loader->getCurrentUser()->getUserId()
                );

        $this->afterSave($changed);

        $this->accesslog->logChange($this->request, null, $this->formData);
    }

    /**
     * Hook performing actual save
     *
     * @param string $newCode
     * @param int $userId
     * @return $changed
     */
    abstract public function setReceptionCode($newCode, $userId);
}
