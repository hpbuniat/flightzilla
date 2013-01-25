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
namespace Flightzilla\Model\Kanban;

use Flightzilla\Model\Ticket\Type\Bug,
    Flightzilla\Model\Ticket\Source\Bugzilla;

/**
 * Determine the Kanban-status of tickets
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Status  {

    /**
     * Kanban-Status
     *
     * @var string
     */
    const WAITING = 'waiting_stack';
    const SCREEN_WIP = 'screen_wip';
    const SCREEN_APPROVED = 'screen_approved';
    const DEV_WAITING = 'dev_waiting';
    const DEV_WIP = 'dev_wip';
    const DEV_READY = 'dev_ready';
    const TEST_WAITING = 'test_waiting';
    const TEST_READY = 'test_ready';
    const RELEASE = 'release';

    /**
     * The status-order
     *
     * @var array
     */
    protected $_aStatusOrder = array(
        self::SCREEN_WIP => 1,
        self::WAITING => 2,
        self::DEV_WAITING => 3,
        self::DEV_WIP => 4,
        self::DEV_READY => 5,
        self::TEST_WAITING => 6,
        self::TEST_READY => 7,
        self::SCREEN_APPROVED => 8,
        self::RELEASE => 9,
    );

    /**
     * The tickets
     *
     * @var array
     */
    protected $_aTickets = array();

    /**
     * The ticket-service
     *
     * @var Bugzilla
     */
    protected $_oTicketService;

    /**
     * The result for internal lookup
     *
     * @var array
     */
    protected $_aStatus = array();

    /**
     * The result
     *
     * @var array
     */
    protected $_aResult = array();

    /**
     * The types of the stack
     *
     * @var array
     */
    protected $_aTypes = array();

    /**
     * Should the tickets be grouped by theme or project?
     *
     * @var boolean
     */
    protected $_bGrouped = false;

    /**
     * Init
     *
     * @param  array $aTickets
     * @param  Bugzilla $oTicketService
     */
    public function __construct(array $aTickets, Bugzilla $oTicketService) {
        $this->_aTickets = $aTickets;
        $this->_oTicketService = $oTicketService;
    }

    /**
     * Set the type of the stack
     *
     * @param  array $aTypes
     *
     * @return $this
     */
    public function setTypes(array $aTypes) {
        $this->_aTypes = $aTypes;
        return $this;
    }

    /**
     * Activate grouping for projects
     *
     * @return $this
     */
    public function setGrouped() {
        $this->_bGrouped = true;
        return $this;
    }

    /**
     * Get the result
     *
     * @return array
     */
    public function get() {
        return $this->_aResult;
    }

    /**
     * Process the stack
     *
     * @return $this
     */
    public function process() {
        $this->_init();

        foreach ($this->_aTickets as $oTicket) {
            /* @var $oTicket \Flightzilla\Model\Ticket\AbstractType */

            // use only tickets of the desired type for the kanban board
            $sType = $oTicket->getType();
            if (in_array($sType, $this->_aTypes) !== true) {
                continue;
            }

            $sStatus = self::RELEASE;
            if ($this->_bGrouped === true) {
                $aStack = $oTicket->getDepends();

                foreach ($aStack as $iTicket) {
                    $sStackStatus = $this->_getStatus($iTicket);
                    if ($this->_aStatusOrder[$sStackStatus] < $this->_aStatusOrder[$sStatus]) {
                        $sStatus = $sStackStatus;
                    }
                }

                unset($aStack);
            }
            else {
                $sStatus = $this->_getStatus($oTicket->id());
            }

            $this->_aResult[$sStatus][] = $oTicket;
        }

        return $this;
    }

    /**
     * Init the lookup
     *
     * @return $this
     */
    protected function _init() {

        // pre-init the desired result-structure
        foreach ($this->_aStatusOrder as $sStatus => $iOrder) {
            $this->_aResult[$sStatus] = array();
        }

        // concepts
        $this->_aStatus = array(
            self::SCREEN_WIP => $this->_oTicketService->getOpenConcepts(),
            self::SCREEN_APPROVED => $this->_oTicketService->getBugsWithFlag(\Flightzilla\Model\Ticket\Type\Bug::FLAG_SCREEN, Bugzilla::BUG_FLAG_GRANTED),
        );

        // stack
        $this->_aStatus[self::WAITING] = $this->_oTicketService->getFilteredList($this->_oTicketService->getUnworkedWithoutOrganization(), $this->_aStatus[self::SCREEN_WIP]);

        // testing
        $this->_aStatus[self::TEST_WAITING] = $this->_oTicketService->getBugsWithFlag(\Flightzilla\Model\Ticket\Type\Bug::FLAG_TESTING, Bugzilla::BUG_FLAG_REQUEST);
        $this->_aStatus[self::TEST_READY] = $this->_oTicketService->getFixedBugsInBranch();

        // development wip, waiting
        $this->_aStatus[self::DEV_WIP] = $this->_oTicketService->getFilteredList($this->_oTicketService->getInprogress(), $this->_aStatus[self::SCREEN_WIP]);
        $this->_aStatus[self::DEV_WAITING] = $this->_oTicketService->getFilteredList($this->_oTicketService->getWaiting(), $this->_aStatus[self::DEV_WIP]);

        // development - ready
        $aFixedWithoutTesting = $this->_oTicketService->getFilteredList($this->_oTicketService->getFixedBugsUnknown(), $this->_aStatus[self::TEST_WAITING]);
        $this->_aStatus[self::DEV_READY] = $this->_oTicketService->getFilteredList($aFixedWithoutTesting, $this->_aStatus[self::SCREEN_APPROVED]);

        // finished
        $this->_aStatus[self::RELEASE] = $this->_oTicketService->getFixedBugsInTrunk();

        return $this;
    }

    /**
     * Get the status of a Ticket
     *
     * @param  int $iTicket
     *
     * @return string
     */
    protected function _getStatus($iTicket) {
        $sReturnStatus = self::WAITING;
        foreach ($this->_aStatusOrder as $sStatus => $iOrder) {
            if (isset($this->_aStatus[$sStatus][$iTicket]) === true) {
                $sReturnStatus = $sStatus;
                break;
            }
        }

        if ($sReturnStatus === self::WAITING) {
            $oTicket = $this->_oTicketService->getBugById($iTicket);
            if ($oTicket->isClosed() === true) {
                $sReturnStatus = self::RELEASE;
            }
        }

        return $sReturnStatus;
    }

}
