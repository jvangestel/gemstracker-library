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
 * @author Matijs de Jong
 * @since 1.0
 * @version 1.1
 * @package MUtil
 * @subpackage Lazy
 */

class MUtil_Lazy_Alternate extends MUtil_Lazy_LazyAbstract
{
    private $_values;
    private $_count;
    private $_current;

    public function __construct(array $values)
    {
        $this->_values  = array_values($values);
        $this->_count   = count($values);
        $this->_current = 0;

        if (0 == $this->_count) {
            throw new MUtil_Lazy_LazyException('Class ' . __CLASS__ . ' needs at least one value as an argument.');

        } elseif (1 == $this->_count) {
            $this->_count++;
            $this->_values[] = null;
        }
    }

    /**
     * The functions that returns the value.
     *
     * Returning an instance of MUtil_Lazy_LazyInterface is allowed.
     *
     * @param MUtil_Lazy_StackInterface $stack A MUtil_Lazy_StackInterface object providing variable data
     * @return mixed
     */
    protected function _getLazyValue(MUtil_Lazy_StackInterface $stack)
    {
        if ($this->_current >= $this->_count) {
            $this->_current = 0;
        }

        return $this->_values[$this->_current++];
    }
}
