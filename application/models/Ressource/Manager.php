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
 * The ressource manager handles ressources and their duties
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Model_Ressource_Manager {

    /**
     *
     */
    protected $_aRessources = array();

    /**
     *
     */
    public function __construct() {

    }

    /**
     *
     */
    public function registerRessource(Model_Ressource_Human $oHuman) {
        $this->_aRessources[$oHuman->getName()] = $oHuman;
        return $this;
    }

    /**
     *
     */
    public function addTicket(Model_Ticket_Type_Bug $oTicket) {
        $aTimes = $oTicket->getWorkedHours();
        if (empty($aTimes) !== true) {
            foreach ($aTimes as $aTime) {
                $sRessource = $aTime['user'];
                if (isset($this->_aRessources[$sRessource]) === true) {
                    $this->_aRessources[$sRessource]->addTicket($aTime);
                }
            }
        }

        return $this;
    }

    /**
     *
     */
    public function addProject(Model_Project_Container $oProject) {
        if (empty($this->_aRessources) === true) {
            throw new Model_Ressource_Manager_Exception(Model_Ressource_Manager_Exception::NO_AVAILABLE_RESSOURCES);
        }

        return $this;
    }

    /**
     * Get all ressources
     *
     * @return array
     */
    public function getRessources() {
        return $this->_aRessources;
    }

    /**
     * Get a specific ressource
     *
     * @param  string $sName
     *
     * @return Model_Ressource_Human
     *
     * @throws InvalidArgumentException
     */
    public function getRessource($sName) {
        if (isset($this->_aRessources[$sName]) === true) {
            return $this->_aRessources[$sName];
        }

        throw new InvalidArgumentException('name not known');
    }
}