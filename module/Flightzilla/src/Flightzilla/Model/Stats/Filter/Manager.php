<?php
/**
 * flightzilla
 *
 * Copyright (c) 2012-2013, Hans-Peter Buniat <hpbuniat@googlemail.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in
 * the documentation and/or other materials provided with the
 * distribution.
 *
 * * Neither the name of Hans-Peter Buniat nor the names of his
 * contributors may be used to endorse or promote products derived
 * from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package flightzilla
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace Flightzilla\Model\Stats\Filter;

use Flightzilla\Model\Reflector;
use Flightzilla\Model\Ticket\Type\Bug;

/**
 * Handle the integrity of tickets & their workflow
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Manager {

    /**
     * The active constraints
     *
     * @var array
     */
    protected $_aConstraints = array();

    /**
     * Number of stack-entries
     *
     * @var int
     */
    protected $_iEntries;

    /**
     * Add a constraint to the constraint-stack
     *
     * @param  string $sConstraint
     * @param  mixed $mPayload
     *
     * @return $this
     */
    public function addConstraint($sConstraint, $mPayload) {
        $this->_aConstraints[] = array(
            'callback' => array(__NAMESPACE__ . sprintf('\Constraint\%s', $sConstraint), 'check'),
            'payload' => $mPayload
        );
        return $this;
    }

    /**
     * Get the registered constraints
     *
     * @return array
     */
    public function getConstraints() {
        return $this->_aConstraints;
    }

    /**
     * Reset the constraints
     *
     * @return $this
     */
    public function resetConstraints() {
        $this->_aConstraints = array();
        return $this;
    }

    /**
     * Check a list of tickets, if they pass all constraints
     *
     * @param  array $aTickets
     *
     * @return array
     */
    public function check(array $aTickets = array()) {
        $aStack = array();
        $this->_iEntries = 0;

        foreach ($aTickets as $oTicket) {
            /* @var Bug $oTicket */

            $bPass = true;
            foreach ($this->_aConstraints as $aCallback) {
                if (call_user_func_array($aCallback['callback'], array($oTicket, $aCallback['payload'])) === false) {
                    $bPass = false;
                }
            }

            if ($bPass === true) {
                $this->_iEntries++;
                $aStack[$oTicket->id()] = $oTicket;
            }
        }

        return $aStack;
    }

    /**
     * Get the number of entries
     *
     * @return int
     */
    public function getEntryCount() {
        return $this->_iEntries;
    }
}
