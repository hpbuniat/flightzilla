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
namespace Flightzilla\Model\Ticket\Integrity;

use Flightzilla\Model\Reflector;

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
    protected $_aConstraints = array(
        \Flightzilla\Model\Ticket\Integrity\Constraint\TicketAge::NAME,
        \Flightzilla\Model\Ticket\Integrity\Constraint\FlagAge::NAME,
        \Flightzilla\Model\Ticket\Integrity\Constraint\OrganizationWithoutDue::NAME,
        \Flightzilla\Model\Ticket\Integrity\Constraint\WorkedWithoutEstimation::NAME,
        \Flightzilla\Model\Ticket\Integrity\Constraint\ResolvedTestFailed::NAME
    );

    /**
     * The ticket-source
     *
     * @var \Flightzilla\Model\Ticket\AbstractSource
     */
    protected $_oTicketSource = null;

    /**
     * Number of stack-entries
     *
     * @var int
     */
    protected $_iEntries;

    /**
     * Create the integrity-manager
     *
     * @param \Flightzilla\Model\Ticket\AbstractSource $oTicketService
     */
    public function __construct(\Flightzilla\Model\Ticket\AbstractSource $oTicketService) {
        $this->_oTicketSource = $oTicketService;
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
            foreach ($this->_aConstraints as $sConstraint) {
                $aCallback = array(__NAMESPACE__ . sprintf('\Constraint\%s', $sConstraint), 'check');
                if (empty($aStack[$sConstraint]) === true) {
                    $aStack[$sConstraint] = array(
                        'description' => Reflector::getClassComment($aCallback[0]),
                        'stack' => array()
                    );
                }

                $bPass = call_user_func_array($aCallback, array($oTicket, $this->_oTicketSource));
                if ($bPass === false) {
                    $this->_iEntries++;
                    $aStack[$sConstraint]['stack'][] = $oTicket;
                    break;
                }
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
