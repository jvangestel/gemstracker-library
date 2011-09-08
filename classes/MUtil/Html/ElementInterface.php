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
 */

/**
 * 
 * @author Matijs de Jong
 * @since 1.0
 * @version 1.1
 * @package MUtil
 * @subpackage Html
 */

/**
 * The ElementInterface defines an Html Element as not just implementing
 * the HtmlInterface but also as an object that can be accessed as array 
 * object through the ArrayAccess, Countable and IteratorAggregate 
 * interfaces.
 * 
 * Usually you should just extend the HtmlElement class. This interface
 * is actually only used when you want to "fake" the full element, e.g.
 * by having a Sequence of elements (i.e. a document fragment) or an 
 * object "posing" as a contained element, e.g. the RepeatRenderer class.
 * 
 * @see MUtil_Html_HtmlInterface 
 * @see MUtil_Html_HtmlElement
 * @see MUtil_Html_RepeatRenderer
 * @see MUtil_Html_Sequence
 * 
 * @author Matijs de Jong
 * @package MUtil
 * @subpackage Html
 */
interface MUtil_Html_ElementInterface extends MUtil_Html_HtmlInterface, ArrayAccess, Countable, IteratorAggregate
{
    /**
     * Add a value to the element. 
     * 
     * Depending on the value type the value may be added as an attribute,
     * set a parameter of the element or just be added to the main content.
     * 
     * Adding to the main content should be the default action.
     * 
     * @param mixe $value
     */
    public function append($value);
    
    /**
     * Most Html elements have a tag name, but "document fragments" like 
     * @see MUtil_Html_Sequence may return null.
     * 
     * @return string The tag name or null if this element does not have one
     */
    public function getTagName();
    
    // inherited: public function render(Zend_View_Abstract $view);
}
