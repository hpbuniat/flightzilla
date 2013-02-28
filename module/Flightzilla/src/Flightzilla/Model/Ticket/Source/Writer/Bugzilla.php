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
namespace Flightzilla\Model\Ticket\Source\Writer;

use Flightzilla\Model\Ticket\Type\Bug;

/**
 * Write changes into the source bugzilla
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
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

        $sPayload = $oTicket->getFlagName(Bug::FLAG_TESTING);
        if (empty($sPayload) !== true) {
            $this->_aPayload['comment'] = 'Please review the changes!';
            $this->_getTestingRequestee($oTicket, $sPayload);

        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \Flightzilla\Model\Ticket\Source\AbstractWriter::reTest()
     */
    public function reTest(\Flightzilla\Model\Ticket\AbstractType $oTicket, $mPayload) {
        $this->_getCommon($oTicket);

        $sPayload = $oTicket->getFlagName(Bug::FLAG_TESTING, \Flightzilla\Model\Ticket\Source\Bugzilla::BUG_FLAG_DENIED);
        if (empty($sPayload) !== true) {
            $this->_aPayload['comment'] = 'Please test again!';
            $this->_getTestingRequestee($oTicket, $sPayload);
        }

        return $this;
    }

    /**
     * Get the testing-requestee
     *
     * @param  \Flightzilla\Model\Ticket\AbstractType $oTicket
     * @param  string $sPayload
     *
     * @return $this
     */
    protected function _getTestingRequestee(\Flightzilla\Model\Ticket\AbstractType $oTicket, $sPayload) {
        $this->_aPayload[$sPayload] = \Flightzilla\Model\Ticket\Source\Bugzilla::BUG_FLAG_REQUEST;
        if ($oTicket->isType(Bug::TYPE_BUG) !== true) {
            $sRequesteePayload = str_replace('flag', 'requestee', $sPayload);
            $this->_aPayload[$sRequesteePayload] = $oTicket->getReporter();
        }

        $this->_aPayload['comment'] .=  PHP_EOL . 'Test-Server: ' . (($oTicket->isMerged() === true) ? 'Stable' : 'Development');
        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \Flightzilla\Model\Ticket\Source\AbstractWriter::setMerged()
     */
    public function setMerged(\Flightzilla\Model\Ticket\AbstractType $oTicket, $mPayload) {
        $this->_getCommon($oTicket);

        $sPayload = $oTicket->getFlagName(Bug::FLAG_MERGE);
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

        $sPayload = $oTicket->getFlagName(Bug::FLAG_TESTSERVER);
        if (empty($sPayload) !== true) {
            $this->_aPayload[$sPayload] = \Flightzilla\Model\Ticket\Source\Bugzilla::BUG_FLAG_GRANTED;
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \Flightzilla\Model\Ticket\Source\AbstractWriter::setUpdateTestserver()
     */
    public function setUpdateTestserver(\Flightzilla\Model\Ticket\AbstractType $oTicket, $mPayload) {
        $this->_getCommon($oTicket);

        $sPayload = $oTicket->getFlagName(Bug::FLAG_TESTSERVER);
        if (empty($sPayload) !== true) {
            $this->_aPayload[$sPayload] = \Flightzilla\Model\Ticket\Source\Bugzilla::BUG_FLAG_REQUEST;
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \Flightzilla\Model\Ticket\Source\AbstractWriter::setDbChanged()
     */
    public function setDbChanged(\Flightzilla\Model\Ticket\AbstractType $oTicket, $mPayload) {
        $this->_getCommon($oTicket);

        $sPayload = $oTicket->getFlagName(Bug::FLAG_DBCHANGE, \Flightzilla\Model\Ticket\Source\Bugzilla::BUG_FLAG_REQUEST);
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
     * (non-PHPdoc)
     * @see \Flightzilla\Model\Ticket\Source\AbstractWriter::setAssigned()
     */
    public function setAssigned(\Flightzilla\Model\Ticket\AbstractType $oTicket, $mPayload) {
        $sStatus = Bug::STATUS_ASSIGNED;
        if (empty($mPayload) !== true and $oTicket->getAssignee() !== $mPayload) {
            if (empty($this->_aPayload['comment']) === true) {
                $this->_aPayload['comment'] = '';
            }

            $this->_aPayload['comment'] = $this->_aPayload['comment'] . PHP_EOL . PHP_EOL . sprintf('re-assigned ticket from %s to %s', $oTicket->getAssignee(), $mPayload);
            $this->_aPayload['assigned_to'] = $mPayload;
            $sStatus = Bug::STATUS_CONFIRMED;
        }

        return $this->setStatus($oTicket, $sStatus);
    }

    /**
     * (non-PHPdoc)
     * @see \Flightzilla\Model\Ticket\Source\AbstractWriter::setResolved()
     */
    public function setResolved(\Flightzilla\Model\Ticket\AbstractType $oTicket, $mPayload) {
        $this->_aPayload['resolution'] = Bug::RESOLUTION_FIXED;
        if (empty($this->_aPayload['comment']) === true) {
            $this->_aPayload['comment'] = 'Finished!';
        }

        return $this->setStatus($oTicket, Bug::STATUS_RESOLVED);
    }

    /**
     * (non-PHPdoc)
     * @see \Flightzilla\Model\Ticket\Source\AbstractWriter::setConfirmed()
     */
    public function setConfirmed(\Flightzilla\Model\Ticket\AbstractType $oTicket, $mPayload) {
        if (empty($this->_aPayload['comment']) === true and $oTicket->getStatus() !== Bug::STATUS_REOPENED) {
            $this->_aPayload['comment'] = 'Paused!';
        }

        return $this->setStatus($oTicket, Bug::STATUS_CONFIRMED);
    }

    /**
     * (non-PHPdoc)
     * @see \Flightzilla\Model\Ticket\Source\AbstractWriter::setComment()
     */
    public function setComment(\Flightzilla\Model\Ticket\AbstractType $oTicket, $mPayload) {
        $this->_getCommon($oTicket);
        if (empty($mPayload) !== true) {
            if (empty($this->_aPayload['comment']) === true) {
                $this->_aPayload['comment'] = '';
            }

            $this->_aPayload['comment'] = $mPayload . PHP_EOL . $this->_aPayload['comment'];
        }

        return $this;
    }
}
