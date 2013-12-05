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
namespace Flightzilla\Model\Ticket\Type;

use Flightzilla\Model\Timeline\Date;

/**
 * A Project
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Project extends Bug {

    /**
     * A list of projects, which this project depends on
     *
     * @var array
     */
    protected $_aDependentProjects = array();

    /**
     * A list of projects, which are blocked by this project
     *
     * @var array
     */
    protected $_aBlockedProjects = array();

    /**
     * Are there only concept-tickets
     *
     * @var boolean
     */
    protected $_bOnlyConcepts = null;

    /**
     * The start-dates of depended tickets
     *
     * @var array
     */
    protected $_aStartDates = array();

    /**
     * Get start date as timestamp.
     * Start date is
     * - the end of its predecessor
     * - the first start-date of a blocking ticket
     * - or the next workday.
     *
     * @param  boolean|null $iCalled
     *
     * @return int
     */
    public function getStartDate($iCalled = null) {
        if ($this->_iStartDate > 0){
            return $this->_iStartDate;
        }

        // is there a predecessor?
        $iPredecessor = $this->getActivePredecessor();
        if ($iPredecessor > 0) {
            $iEndDate = $this->_oBugzilla->getBugById($iPredecessor)->getEndDate($iCalled);
            $this->_iStartDate = strtotime('+1 day ' . Date::START, $iEndDate);
        }
        else {
            // start date of the first ticket in current project
            if ($this->id() !== $iCalled) {
                $aDepends   = $this->getDepends();

                $iCalled = (is_null($iCalled) === true) ? $this->id() : $iCalled;
                foreach ($aDepends as $iTicket) {
                    $this->_aStartDates[$iTicket] = (float) $this->_oBugzilla->getBugById($iTicket)->getStartDate($iCalled);
                }
            }

            if (empty($this->_aStartDates) !== true) {
                asort($this->_aStartDates);
                $this->_iStartDate = reset($this->_aStartDates);
            }
        }

        if (empty($this->_iStartDate) === true) {
            $this->_iStartDate = strtotime('+1 day ' . Date::START);
        }

        $this->_iStartDate = $this->_oDate->getNextWorkday($this->_iStartDate);

        return $this->_iStartDate;
    }

    /**
     * Get the end-date as timestamp.
     *
     * @param  boolean|null $iCalled
     *
     * @return int
     */
    public function getEndDate($iCalled = null) {

        if ($this->_iEndDate > 0) {
            return $this->_iEndDate;
        }

        $sDeadline = $this->getDeadline();
        if (empty($sDeadline) !== true) {
            $sEndDate = $sDeadline;
            $this->_iEndDate = strtotime(str_replace('00:00:00', Date::END, $sEndDate));
        }

        if (empty($this->_iEndDate) or $this->_iEndDate < time()) {
            // End date of the last ticket in current project
            $aEndDates = array();
            $aDepends   = $this->getDepends();

            $iCalled = (is_null($iCalled) === true) ? $this->id() : $iCalled;
            foreach ($aDepends as $iTicket) {
                $aEndDates[$iTicket] = (float) $this->_oBugzilla->getBugById($iTicket)->getEndDate($iCalled);
            }

            asort($aEndDates);

            $this->_iEndDate = end($aEndDates);

            $this->_iEndDate = $this->_oDate->getNextWorkday($this->_iEndDate);
        }

        return $this->_iEndDate;
    }

    /**
     * Return the ticket number of the projects predecessor or 0 if there isn't one.
     *
     * A valid predecessor has the status unconfirmed, confirmed or assigned, and is a project or theme.
     * If there are more than one predecessors, the one with the latest end date will be returned.
     *
     * @return int
     */
    public function getActivePredecessor() {

        if ($this->hasDependencies()) {
            $dependencies = $this->getDependentProjects();

            $aEndDates = array();
            foreach ($dependencies as $dependency) {
                $oTicket = $this->_oBugzilla->getBugById($dependency);
                if ($oTicket->isStatusAtMost(Bug::STATUS_REOPENED) === true) {
                    $aEndDates[$oTicket->id()] = $oTicket->getEndDate();
                }
            }

            if (empty($aEndDates) === true) {
                return 0;
            }

            arsort($aEndDates);
            return key($aEndDates);
        }

        return 0;
    }

    /**
     * Determine all dependent projects.
     *
     * @return array
     */
    public function getDependentProjects() {

        if (empty($this->_aDependentProjects) === false) {
            return $this->_aDependentProjects;
        }

        foreach ($this->getDependsAsStack() as $oTicket) {
            /* @var Bug $oTicket */
            try {
                if ($oTicket->isProject() === true) {
                    $this->_aDependentProjects[$oTicket->id()] = $oTicket->id();
                }
            }
            catch (\Exception $e) {
                /* happens, if a bug is not found, which is ok for closed bugs */
            }
        }

        // recursively get dependent projects
        foreach ($this->_aDependentProjects as $iTicket) {
            $oTicket = $this->_oBugzilla->getBugById($iTicket);

            /* @var Project $oTicket */
            $this->_aDependentProjects = array_merge($this->_aDependentProjects, $oTicket->getDependentProjects());
        }

        return $this->_aDependentProjects;
    }

    /**
     * Determine all blocked projects
     *
     * @return array
     */
    public function getBlockedProjects() {
        if (empty($this->_aBlockedProjects) === false) {
            return $this->_aBlockedProjects;
        }

        foreach ($this->getBlockedAsStack() as $oTicket) {
            /* @var Bug $oTicket */
            try {
                if ($oTicket->isProject() === true) {
                    $this->_aBlockedProjects[$oTicket->id()] = $oTicket->id();
                }
            }
            catch (\Exception $e) {
                /* happens, if a bug is not found, which is ok for closed bugs */
            }
        }

        // recursively get dependent projects
        foreach ($this->_aBlockedProjects as $iTicket) {
            $oTicket = $this->_oBugzilla->getBugById($iTicket);

            /* @var Project $oTicket */
            $this->_aBlockedProjects = array_merge($this->_aBlockedProjects, $oTicket->getBlockedProjects());
        }

        return $this->_aBlockedProjects;
    }

    /**
     * Are all depending tickets merged?
     *
     * @return boolean
     */
    public function isMerged() {

        if (isset($this->_aMethodCache[Bug::CACHE_ISMERGED]) === false) {
            $bReady = true;
            foreach ($this->getDependsAsStack() as $oTicket) {
                /* @var Bug $oTicket */
                if ($oTicket->couldBeInTrunk() === false) {
                    $bReady = false;
                    break;
                }
            }

            $this->_aMethodCache[Bug::CACHE_ISMERGED] = ($this->hasDevelopment() === true and $bReady === true and empty($this->_aDepends) === false);
        }

        return $this->_aMethodCache[Bug::CACHE_ISMERGED];
    }

    /**
     * Does the project have any development-tickets?
     *
     * @return boolean
     */
    public function hasDevelopment() {
        if (isset($this->_aMethodCache[Bug::CACHE_HASDEVELOPMENT]) === false) {
            $this->_aMethodCache[Bug::CACHE_HASDEVELOPMENT] = true;
            foreach ($this->getDependsAsStack() as $oTicket) {
                /* @var Bug $oTicket */
                if ($oTicket->isConcept() === false) {
                    $this->_aMethodCache[Bug::CACHE_HASDEVELOPMENT] = false;
                }
            }
        }

        return ($this->_aMethodCache[Bug::CACHE_HASDEVELOPMENT] === false);
    }

    /**
     * Check, if a ticket was changed within a given time-limit
     *
     * @param $iLimit
     *
     * @return boolean
     */
    public function isChangedWithinLimit($iLimit) {
        $bIsChanged = false;

        $iTime = time();
        foreach($this->getDependsAsStack() as $oTicket) {
            /* @var Bug $oTicket */
            if (($iTime - $oTicket->getLastActivity()) < $iLimit) {
                $bIsChanged = true;
                break;
            }
        }

        return $bIsChanged;
    }

    /**
     * Get the revenue-score-estimation based on work-days (estimation)
     *
     * @return float
     */
    public function getRevenueScoreEstimation() {
        $fRevenue = (float) $this->getRevenue();
        $fProbability = (float) $this->getRevenueProbability();
        $fEstimation = (float) $this->getEstimationTimeOfDependencies();

        $fReturn = 0;
        if ($fRevenue > 0 and $fProbability > 0 and $fEstimation > 0) {
            $fReturn = round(($fRevenue / $fProbability) / ($fEstimation / Date::AMOUNT), 2);
        }

        return $fReturn;
    }

    /**
     * Get the revenue-estimation based on work-days (actual- + left-time)
     *
     * @return float
     */
    public function getRevenueScoreActual() {
        $fRevenue = (float) $this->getRevenue();
        $fProbability = (float) $this->getRevenueProbability();
        $fTime = (float) $this->getActualTimeOfDependencies() + (float) $this->getLeftTimeOfDependencies();

        $fReturn = 0;
        if ($fRevenue > 0 and $fProbability > 0 and $fTime > 0) {
            $fReturn = round(($fRevenue / $fProbability) / ($fTime / Date::AMOUNT), 2);
        }

        return $fReturn;
    }

    /**
     * Get the remaining hours of the project
     *
     * @return float
     */
    public function getLeftHours() {
        return $this->getLeftTimeOfDependencies();
    }

    /**
     * Get the estimation
     *
     * @return float
     */
    public function getEstimation() {

        return $this->getEstimationTimeOfDependencies(array(
            Bug::TYPE_BUG,
            Bug::TYPE_CONCEPT,
            Bug::TYPE_FEATURE,
            Bug::TYPE_HOMELESS_FEATURE
        ));
    }

    /**
     * Get the actual invested time
     *
     * @return float
     */
    public function getActualTime() {

        return $this->getActualTimeOfDependencies(array(
            Bug::TYPE_BUG,
            Bug::TYPE_CONCEPT,
            Bug::TYPE_FEATURE,
            Bug::TYPE_HOMELESS_FEATURE
        ));
    }

    /**
     * Check if the project has dependencies to other products
     *
     * @return bool
     */
    public function hasDependenciesToOtherProducts() {
        if (isset($this->_aMethodCache[self::CACHE_HAS_DEPENDENCIES_TO_OTHER_PRODUCT]) !== true) {

            $bHasDependencies = false;
            $aCheckTickets = array_merge($this->getDependsAsStack(), $this->getBlockedAsStack());
            foreach ($aCheckTickets as $oTicket) {
                /* @var Bug $oTicket */
                if ($oTicket->getProductName() !== $this->getProductName()) {
                    $bHasDependencies = true;
                    break;
                }
            }

            $this->_aMethodCache[self::CACHE_HAS_DEPENDENCIES_TO_OTHER_PRODUCT] = $bHasDependencies;
        }

        return $this->_aMethodCache[self::CACHE_HAS_DEPENDENCIES_TO_OTHER_PRODUCT];
    }
}
