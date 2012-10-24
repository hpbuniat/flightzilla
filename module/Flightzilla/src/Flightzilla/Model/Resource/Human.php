<?php
/**
 * flightzilla
 *
 * Copyright (c)2012, Hans-Peter Buniat <hpbuniat@googlemail.com>.
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
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace Flightzilla\Model\Resource;

/**
 * A human resource
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
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
     * @var \Flightzilla\Model\Ticket\Type\Bug[]
     */
    protected $_aTickets = array();

    /**
     * Create the human
     *
     * @param array $aMember
     * @param \Flightzilla\Model\Resource\Human\Timecard $oTimecard
     */
    public function __construct($aMember, \Flightzilla\Model\Resource\Human\Timecard $oTimecard) {

        $this->_oTimecard = $oTimecard;
        $this->_sName = $aMember['name'];
        $this->_aData = $aMember;
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
     * @param  \Flightzilla\Model\Ticket\Type\Bug $oTicket
     *
     * @return Human
     */
    public function addTicket(\Flightzilla\Model\Ticket\Type\Bug $oTicket) {

        $this->_aTickets[$oTicket->id()] = $oTicket;
        $this->_oTimecard->handle($oTicket);

        return $this;
    }

    /**
     * Return the ticket with next higher priority which is confirmed or assigned.
     *
     * It must not be a theme or project.
     *
     * @param \Flightzilla\Model\Ticket\Type\Bug $oTicket
     *
     * @return \Flightzilla\Model\Ticket\Type\Bug
     */
    public function getNextHigherPriorityTicket(\Flightzilla\Model\Ticket\Type\Bug $oTicket) {

        $nextPrioTicket = $oTicket;
        foreach ($this->_aTickets as $ticket) {
            if (($ticket->getStatus() !== \Flightzilla\Model\Ticket\Type\Bug::STATUS_CONFIRMED
                and $ticket->getStatus() !== \Flightzilla\Model\Ticket\Type\Bug::STATUS_ASSIGNED
                    and $ticket->getStatus() !== \Flightzilla\Model\Ticket\Type\Bug::STATUS_REOPENED)
                or $ticket->isProject()
                or $ticket->isTheme()
            ) {

                continue;
            }

            if ($ticket->getPriority(true) > $nextPrioTicket->getPriority(true)) {
                $nextPrioTicket = $ticket;
            }
            elseif ($ticket->getPriority(true) === $nextPrioTicket->getPriority(true)) {
                if ($ticket->getSeverity(true) > $nextPrioTicket->getSeverity(true)) {
                    $nextPrioTicket = $ticket;
                }
            }
        }

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
