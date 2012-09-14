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
 * The resource manager handles resources and their duties
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Model_Resource_Manager {

    /**
     *
     */
    protected $_aResources = array();

    /**
     *
     */
    public function __construct() {

    }

    /**
     *
     */
    public function registerResource(Model_Resource_Human $oHuman) {
        $this->_aResources[$oHuman->getName()] = $oHuman;
        return $this;
    }

    /**
     * @param Model_Ticket_Type_Bug $oTicket
     * @param bool                  $bOnlyWorkedBugs
     *
     * @return Model_Resource_Manager
     */
    public function addTicket(Model_Ticket_Type_Bug $oTicket, $bOnlyWorkedBugs = true) {

        if ($bOnlyWorkedBugs === true) {
            $aTimes = $oTicket->getWorkedHours();
            if (empty($aTimes) !== true) {
                foreach ($aTimes as $aTime) {
                    if (isset($this->_aResources[$aTime['user']]) === true) {
                        $this->addTicket($oTicket, false);
                    }
                }
            }
        }
        else {
            $sResource = $oTicket->getAssignee();
            if (isset($this->_aResources[$sResource]) === true) {
                $this->_aResources[$sResource]->addTicket($oTicket);
            }
        }

        return $this;
    }

    /**
     *
     */
    public function addProject(Model_Project_Container $oProject) {
        if (empty($this->_aResources) === true) {
            throw new Model_Resource_Manager_Exception(Model_Resource_Manager_Exception::NO_AVAILABLE_RESOURCES);
        }

        return $this;
    }

    /**
     * Get all resources
     *
     * @return array
     */
    public function getResources() {
        return $this->_aResources;
    }

    /**
     * Get a specific resource
     *
     * @param  string $sName
     *
     * @return Model_Resource_Human
     *
     * @throws InvalidArgumentException
     */
    public function getResource($sName) {
        if (isset($this->_aResources[$sName]) === true) {
            return $this->_aResources[$sName];
        }

        throw new InvalidArgumentException('name "' . $sName . '"not known');
    }
}