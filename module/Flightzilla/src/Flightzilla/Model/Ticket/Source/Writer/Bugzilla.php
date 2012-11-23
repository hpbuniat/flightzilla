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
namespace Flightzilla\Model\Ticket\Source\Writer;


/**
 * Write changes into the source bugzilla
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Bugzilla extends \Flightzilla\Model\Ticket\Source\AbstractWriter {

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
     * @see \Flightzilla\Model\Ticket\Source\AbstractWriter::getPayload()
     */
    public function getPayload() {
        $this->_aPayload['token'] = $this->_sToken;
        $this->_aPayload['id'] = $this->_sId;

        return $this->_aPayload;
    }

    /**
     * Get common-data which is needed for bugzilla
     *
     * @param  \Flightzilla\Model\Ticket\AbstractType $oTicket
     *
     * @return \Flightzilla\Model\Ticket\Source\AbstractWriter
     */
    protected function _getCommon(\Flightzilla\Model\Ticket\AbstractType $oTicket) {
        $this->_sToken = (string) $oTicket->token;
        $this->_sId = (string) $oTicket->bug_id;

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \Flightzilla\Model\Ticket\Source\AbstractWriter::setTestingRequest()
     */
    public function setTestingRequest(\Flightzilla\Model\Ticket\AbstractType $oTicket, $mPayload) {
        $this->_getCommon($oTicket);

        $sPayload = $oTicket->getFlagName(\Flightzilla\Model\Ticket\Type\Bug::FLAG_TESTING);
        if (empty($sPayload) !== true) {
            $this->_aPayload[$sPayload] = \Flightzilla\Model\Ticket\Source\Bugzilla::BUG_FLAG_REQUEST;
            if ($oTicket->isType(\Flightzilla\Model\Ticket\Type\Bug::TYPE_BUG) !== true) {
                $sRequesteePayload = str_replace('flag', 'requestee', $sPayload);
                $this->_aPayload[$sRequesteePayload] = $oTicket->getReporter();
            }

            $this->_aPayload['comment'] = 'Please review the changes!' . PHP_EOL . 'Test-Server: ' . (($oTicket->isMerged() === true) ? 'Stable' : 'Development');
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \Flightzilla\Model\Ticket\Source\AbstractWriter::reTest()
     */
    public function reTest(\Flightzilla\Model\Ticket\AbstractType $oTicket, $mPayload) {
        $this->_getCommon($oTicket);

        $sPayload = $oTicket->getFlagName(\Flightzilla\Model\Ticket\Type\Bug::FLAG_TESTING, \Flightzilla\Model\Ticket\Source\Bugzilla::BUG_FLAG_DENIED);
        if (empty($sPayload) !== true) {
            $this->_aPayload[$sPayload] = \Flightzilla\Model\Ticket\Source\Bugzilla::BUG_FLAG_REQUEST;
            $this->_aPayload['comment'] = 'Please test again!' . PHP_EOL . 'Test-Server: ' . (($oTicket->isMerged() === true) ? 'Stable' : 'Development');
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \Flightzilla\Model\Ticket\Source\AbstractWriter::setMerged()
     */
    public function setMerged(\Flightzilla\Model\Ticket\AbstractType $oTicket, $mPayload) {
        $this->_getCommon($oTicket);

        $sPayload = $oTicket->getFlagName(\Flightzilla\Model\Ticket\Type\Bug::FLAG_MERGE);
        if (empty($sPayload) !== true) {
            $this->_aPayload[$sPayload] = \Flightzilla\Model\Ticket\Source\Bugzilla::BUG_FLAG_GRANTED;
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \Flightzilla\Model\Ticket\Source\AbstractWriter::setStaged()
     */
    public function setStaged(\Flightzilla\Model\Ticket\AbstractType $oTicket, $mPayload) {
        $this->_getCommon($oTicket);

        $sPayload = $oTicket->getFlagName(\Flightzilla\Model\Ticket\Type\Bug::FLAG_TESTSERVER);
        if (empty($sPayload) !== true) {
            $this->_aPayload[$sPayload] = \Flightzilla\Model\Ticket\Source\Bugzilla::BUG_FLAG_GRANTED;
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \Flightzilla\Model\Ticket\Source\AbstractWriter::setDbChanged()
     */
    public function setDbChanged(\Flightzilla\Model\Ticket\AbstractType $oTicket, $mPayload) {
        $this->_getCommon($oTicket);

        $sPayload = $oTicket->getFlagName(\Flightzilla\Model\Ticket\Type\Bug::FLAG_DBCHANGE, \Flightzilla\Model\Ticket\Source\Bugzilla::BUG_FLAG_REQUEST);
        if (empty($sPayload) !== true) {
            $this->_aPayload[$sPayload] = \Flightzilla\Model\Ticket\Source\Bugzilla::BUG_FLAG_GRANTED;
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \Flightzilla\Model\Ticket\Source\AbstractWriter::setEstimation()
     */
    public function setEstimation(\Flightzilla\Model\Ticket\AbstractType $oTicket, $mPayload) {
        $this->_getCommon($oTicket);
        if (empty($mPayload) !== true) {
            $this->_aPayload['estimated_time'] = $mPayload;
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \Flightzilla\Model\Ticket\Source\AbstractWriter::setWorked()
     */
    public function setWorked(\Flightzilla\Model\Ticket\AbstractType $oTicket, $mPayload) {
        $this->_getCommon($oTicket);
        if (empty($mPayload) !== true) {
            $this->_aPayload['work_time'] = $mPayload;
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \Flightzilla\Model\Ticket\Source\AbstractWriter::setStatus()
     */
    public function setStatus(\Flightzilla\Model\Ticket\AbstractType $oTicket, $mPayload) {
        $this->_getCommon($oTicket);

        $this->_aPayload['bug_status'] = $mPayload;
        return $this;
    }

    /**
     * Set the status to assigned
     *
     * @param  \Flightzilla\Model\Ticket\AbstractType $oTicket
     * @param  mixed $mPayload
     *
     * @return $this
     */
    public function setAssigned(\Flightzilla\Model\Ticket\AbstractType $oTicket, $mPayload) {
        return $this->setStatus($oTicket, \Flightzilla\Model\Ticket\Type\Bug::STATUS_ASSIGNED);
    }

    /**
     * Set the status to resolved
     *
     * @param  \Flightzilla\Model\Ticket\AbstractType $oTicket
     * @param  mixed $mPayload
     *
     * @return $this
     */
    public function setResolved(\Flightzilla\Model\Ticket\AbstractType $oTicket, $mPayload) {
        $this->_aPayload['comment'] = 'Finished!';
        $this->_aPayload['resolution'] = \Flightzilla\Model\Ticket\Type\Bug::RESOLUTION_FIXED;


        return $this->setStatus($oTicket, \Flightzilla\Model\Ticket\Type\Bug::STATUS_RESOLVED);
    }
}
