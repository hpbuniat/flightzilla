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
namespace Flightzilla\Model\Resource;

use \Flightzilla\Model\Ticket\Type\Bug;

/**
 * A human resource
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Human {

    /**
     * The resource' timecard
     *
     * @var \Flightzilla\Model\Resource\Human\Timecard
     */
    protected $_oTimecard;

    /**
     * The name of the resource
     *
     * @var string
     */
    protected $_sName;

    /**
     * The resource-data
     *
     * @var array
     */
    protected $_aData = array();

    /**
     * Tickets which the resource is assigned to
     *
     * @var Bug[]
     */
    protected $_aTickets = array();

    /**
     * Those tickets have already been the 'next' higher-ones
     *
     * @var int[]
     */
    protected $_aNextHigherPrioTickets = array();

    /**
     * Create the human
     *
     * @param string $sLogin
     * @param array $aMember
     * @param \Flightzilla\Model\Resource\Human\Timecard $oTimecard
     */
    public function __construct($sLogin, $aMember, \Flightzilla\Model\Resource\Human\Timecard $oTimecard) {

        $this->_oTimecard = $oTimecard;
        $aMember['login'] = $sLogin;
        $this->_sName = $aMember['name'];
        $this->_aData = $aMember;

        $this->_oTimecard->setResource($this->getEmail());
    }

    /**
     * Get the timecard
     *
     * @return \Flightzilla\Model\Resource\Human\Timecard
     */
    public function getTimecard() {
        return $this->_oTimecard;
    }

    /**
     * Get the name
     *
     * @return string
     */
    public function getName() {

        return $this->_sName;
    }

    /**
     * Get the email
     *
     * @return string
     */
    public function getEmail() {
        return $this->_aData['mail'];
    }

    /**
     * Get the login
     *
     * @return string
     */
    public function getLogin() {
        return $this->_aData['login'];
    }

    /**
     * Get the gravatar-image
     *
     * @return string
     */
    public function getGravatar() {
        return (empty($this->_aData['gravatar']) === true) ? $this->getEmail() : $this->_aData['gravatar'];
    }

    /**
     * Add the all resource corresponding tickets
     *
     * @param  Bug $oTicket
     *
     * @return Human
     */
    public function addTicket(Bug $oTicket) {

        $this->_aTickets[$oTicket->id()] = $oTicket;
        $this->_oTimecard->handle($oTicket);

        return $this;
    }

    /**
     * Return the ticket with next higher priority which is confirmed or assigned.
     *
     * It must not be a theme or project.
     *
     * @param  Bug $oTicket
     * @param  boolean $bOnlyActive
     *
     * @return Bug
     */
    public function getNextHigherPriorityTicket(Bug $oTicket, $bOnlyActive = true) {

        if (isset($this->_aNextHigherPrioTickets[$oTicket->id()]) === true) {
            $this->_aTickets[$this->_aNextHigherPrioTickets[$oTicket->id()]];
        }

        $nextPrioTicket = $oTicket;
        foreach ($this->_aTickets as $ticket) {
            $sStatus = $ticket->getStatus();
            if (($bOnlyActive === true
                and $sStatus !== Bug::STATUS_CONFIRMED
                and $sStatus !== Bug::STATUS_ASSIGNED
                and $sStatus !== Bug::STATUS_REOPENED)
                    or $ticket->isContainer() === true
            ) {

                continue;
            }

            if ($ticket->isStatusAtMost(Bug::STATUS_REOPENED) === true and in_array($ticket->id(), $this->_aNextHigherPrioTickets) !== true) {
                if ($ticket->getPriority(true) > $nextPrioTicket->getPriority(true)) {
                    $nextPrioTicket = $ticket;
                }
                elseif ($ticket->getPriority(true) === $nextPrioTicket->getPriority(true)) {
                    if ($ticket->getSeverity(true) > $nextPrioTicket->getSeverity(true)) {
                        $nextPrioTicket = $ticket;
                    }
                }
            }
        }

        if ($bOnlyActive === true and $oTicket->id() === $nextPrioTicket->id()) {
            $nextPrioTicket = $this->getNextHigherPriorityTicket($oTicket, false);
        }

        $this->_aNextHigherPrioTickets[$oTicket->id()] = $nextPrioTicket->id();
        return $nextPrioTicket;
    }

    /**
     * Return the name, when used in a string context
     *
     * @return string
     */
    public function __toString() {
        return $this->getName();
    }

}
