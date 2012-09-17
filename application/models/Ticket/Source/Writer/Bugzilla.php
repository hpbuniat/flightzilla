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
 * Write changes into the source bugzilla
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Model_Ticket_Source_Writer_Bugzilla extends Model_Ticket_Source_AbstractWriter {

    /**
     * The bugzilla-token
     *
     * @var string
     */
    protected $_sToken;

    /**
     * The bugzilla-id
     *
     * @var string
     */
    protected $_sId;

    /**
     * (non-PHPdoc)
     * @see Model_Ticket_Source_AbstractWriter::getPayload()
     */
    public function getPayload() {
        $this->_aPayload['token'] = $this->_sToken;
        $this->_aPayload['id'] = $this->_sId;

        return $this->_aPayload;
    }

    /**
     * Get common-data which is needed for bugzilla
     *
     * @param  Model_Ticket_AbstractType $oTicket
     *
     * @return Model_Ticket_Source_AbstractWriter
     */
    protected function _getCommon(Model_Ticket_AbstractType $oTicket) {
        $this->_sToken = (string) $oTicket->token;
        $this->_sId = (string) $oTicket->bug_id;

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Model_Ticket_Source_AbstractWriter::setTestingRequest()
     */
    public function setTestingRequest(Model_Ticket_AbstractType $oTicket) {
        $this->_getCommon($oTicket);

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Model_Ticket_Source_AbstractWriter::reTest()
     */
    public function reTest(Model_Ticket_AbstractType $oTicket) {
        $this->_getCommon($oTicket);

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Model_Ticket_Source_AbstractWriter::setMerged()
     */
    public function setMerged(Model_Ticket_AbstractType $oTicket) {
        $this->_getCommon($oTicket);

        return $this;
    }
}
