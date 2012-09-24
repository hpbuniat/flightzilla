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
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

/**
 * A human resource
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Model_Resource_Human {

    /**
     * The resource' timecard
     *
     * @var Model_Resource_Human_Timecard
     */
    protected $_oTimecard;

    /**
     * The name of the resource
     *
     * @var string
     */
    protected $_sName;

    /**
     * Tickets which the resource is assigned to
     *
     * @var Model_Ticket_Type_Bug[]
     */
    protected $_aTickets = array();

    /**
     * Create the human
     *
     * @param string $sName
     * @param Model_Resource_Human_Timecard $oTimecard
     */
    public function __construct($sName, Model_Resource_Human_Timecard $oTimecard) {

        $this->_oTimecard = $oTimecard;
        $this->_sName = $sName;
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
     * Add the all resource corresponding tickets
     *
     * @param  Model_Ticket_Type_Bug $oTicket
     *
     * @return Model_Resource_Human
     */
    public function addTicket(Model_Ticket_Type_Bug $oTicket) {

        $this->_aTickets[$oTicket->id()] = $oTicket;
        $this->_oTimecard->handle($oTicket);

        return $this;
    }

    /**
     * Return the ticket with next higher priority which is confirmed or assigned.
     *
     * It must not be a theme or project.
     *
     * @param Model_Ticket_Type_Bug $oTicket
     *
     * @return \Model_Ticket_Type_Bug
     */
    public function getNextHigherPriorityTicket(Model_Ticket_Type_Bug $oTicket) {

        $nextPrioTicket = $oTicket;
        foreach ($this->_aTickets as $ticket) {
            if (($ticket->getStatus() !== Model_Ticket_Type_Bug::STATUS_CONFIRMED
                and $ticket->getStatus() !== Model_Ticket_Type_Bug::STATUS_ASSIGNED
                    and $ticket->getStatus() !== Model_Ticket_Type_Bug::STATUS_REOPENED)
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

}
