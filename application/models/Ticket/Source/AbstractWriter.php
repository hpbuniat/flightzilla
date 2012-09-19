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
 * Abstract for source-writer
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
abstract class Model_Ticket_Source_AbstractWriter {

    /**
     * The Ticket-Source
     *
     * @var Model_Ticket_AbstractSource
     */
    protected $_oSource;

    /**
     * The payload for the source
     *
     * @var string
     */
    protected $_aPayload;

    /**
     * Set the source, as the source knows the basic communication
     *
     * @param Model_Ticket_AbstractSource $oSource
     */
    public function __construct(Model_Ticket_AbstractSource $oSource) {
        $this->_oSource = $oSource;
    }

    /**
     * Get the payload-data
     *
     * @return array
     */
    abstract public function getPayload();

    /**
     * Set the inital testing-request
     *
     * @param  Model_Ticket_AbstractType $oTicket
     *
     * @return Model_Ticket_Source_AbstractWriter
     */
    abstract public function setTestingRequest(Model_Ticket_AbstractType $oTicket);

    /**
     * Re-test the ticket, after test was not sucessful
     *
     * @param Model_Ticket_AbstractType $oTicket
     *
     * @return Model_Ticket_Source_AbstractWriter
     */
    abstract public function reTest(Model_Ticket_AbstractType $oTicket);

    /**
     * The ticket has been merged
     *
     * @param Model_Ticket_AbstractType $oTicket
     *
     * @return Model_Ticket_Source_AbstractWriter
     */
    abstract public function setMerged(Model_Ticket_AbstractType $oTicket);

    /**
     * The ticket is now on the test-server
     *
     * @param Model_Ticket_AbstractType $oTicket
     *
     * @return Model_Ticket_Source_AbstractWriter
     */
    abstract public function setStaged(Model_Ticket_AbstractType $oTicket);

    /**
     * The db-changes of a ticket are now deployed
     *
     * @param Model_Ticket_AbstractType $oTicket
     *
     * @return Model_Ticket_Source_AbstractWriter
     */
    abstract public function setDbChanged(Model_Ticket_AbstractType $oTicket);
}
