<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Subscribe
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Subscribe;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Subscribe
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 19-Mar-2019 14:02:33
 */
class ThankYouForSubscribingSnippet extends \MUtil_Snippets_SnippetAbstract
{
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
        $html = $this->getHtmlSequence();
        $html->h2($this->_('Thank you for subscribing!'));

        return $html;
    }
}
