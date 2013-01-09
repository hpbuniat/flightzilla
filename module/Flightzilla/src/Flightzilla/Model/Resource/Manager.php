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

/**
 * The resource manager handles resources and their duties
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Manager {

    /**
     * Known resources
     *
     * @var array
     */
    protected $_aResources = array();

    /**
     * A lookup-cache
     *
     * @var array
     */
    protected $_aLookup = array();

    /**
     * The list of handled tickets
     *
     * @var array
     */
    protected $_aHandled = array();

    /**
     * Register a resource
     *
     * @param  \Flightzilla\Model\Resource\Human $oHuman
     *
     * @return $this
     */
    public function registerResource(\Flightzilla\Model\Resource\Human $oHuman) {
        $sName = $oHuman->getName();
        if (empty($this->_aResources[$sName]) === true) {
            $this->_aResources[$sName] = $oHuman;
        }

        return $this;
    }

    /**
     * Add a ticket to a resources the stack
     *
     * @param \Flightzilla\Model\Ticket\Type\Bug $oTicket
     *
     * @return Manager
     */
    public function addTicket(\Flightzilla\Model\Ticket\Type\Bug $oTicket) {
        $iId = $oTicket->id();
        if (empty($this->_aHandled[$iId]) === true) {
            $this->_aHandled[$iId] = $iId;

            try {
                $sResource = (string) $oTicket->getResource();
                if (isset($this->_aResources[$sResource]) === true) {
                    $this->_aResources[$sResource]->addTicket($oTicket);
                }
            }
            catch (\InvalidArgumentException $e) {
                /* nop */
            }

            $aTimes = $oTicket->getWorkedHours();
            if (empty($aTimes) !== true) {
                foreach ($aTimes as $aTime) {
                    if (isset($this->_aResources[$aTime['user']]) === true) {
                        $this->addTicket($oTicket);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Add a project
     *
     * @param  \Flightzilla\Model\Project\Container $oProject
     *
     * @return $this
     *
     * @throws \Flightzilla\Model\Resource\Manager\Exception If there are no resources available
     */
    public function addProject(\Flightzilla\Model\Project\Container $oProject) {
        if (empty($this->_aResources) === true) {
            throw new \Flightzilla\Model\Resource\Manager\Exception(\Flightzilla\Model\Resource\Manager\Exception::NO_AVAILABLE_RESOURCES);
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
     * Get the activity of all resources
     *
     * @param  int $iDays
     *
     * @return array
     */
    public function getActivities($iDays = 7) {
        $aActivities = array();
        foreach ($this->_aResources as $oResource) {
            $aActivity = $oResource->getTimecard()->getTimesAsGantt($iDays);
            if (empty($aActivity) !== true) {
                $aActivities = array_merge($aActivities, $aActivity);
            }
        }

        return $aActivities;
    }

    /**
     * Get a specific resource
     *
     * @param  string $sName
     *
     * @return \Flightzilla\Model\Resource\Human
     *
     * @throws \InvalidArgumentException
     */
    public function getResource($sName) {
        if (isset($this->_aResources[$sName]) === true) {
            return $this->_aResources[$sName];
        }

        throw new \InvalidArgumentException('name "' . $sName . '" not known');
    }

    /**
     * Check, if a resource is known
     *
     * @param  string $sName
     *
     * @return boolean
     */
    public function hasResource($sName) {
        return (isset($this->_aResources[$sName]));
    }

    /**
     * Get a resource by email
     *
     * @param  string $sMail
     *
     * @return string
     */
    public function getResourceByEmail($sMail) {
        if (empty($this->_aLookup[$sMail]) === true) {
            foreach ($this->_aResources as $oHuman) {
                /* @var $oHuman \Flightzilla\Model\Resource\Human */
                if ($oHuman->getEmail() === $sMail) {
                    $this->_aLookup[$sMail] = $oHuman->getName();
                    break;
                }
            }

            if (empty($this->_aLookup[$sMail]) === true) {
                $this->_aLookup[$sMail] = $sMail;
            }
        }

        return $this->_aLookup[$sMail];
    }

    /**
     * Get a resource by login
     *
     * @param  string $sLogin
     *
     * @return string
     */
    public function getResourceByLogin($sLogin) {
        if (empty($this->_aLookup[$sLogin]) === true) {
            foreach ($this->_aResources as $oHuman) {
                /* @var $oHuman \Flightzilla\Model\Resource\Human */
                if ($oHuman->getLogin() === $sLogin) {
                    $this->_aLookup[$sLogin] = $oHuman->getName();
                    break;
                }
            }

            if (empty($this->_aLookup[$sLogin]) === true) {
                $this->_aLookup[$sLogin] = $sLogin;
            }
        }

        return $this->_aLookup[$sLogin];
    }
}
