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
namespace Flightzilla\Model\Ticket\Type;

/**
 * A Project
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
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
     * Are there only concept-tickets
     *
     * @var boolean
     */
    protected $_bOnlyConcepts = null;

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
            $iEndDate = $this->_oBugzilla->getBugById($iPredecessor)->getEndDate();
            $this->_iStartDate = strtotime('+1 day ' . \Flightzilla\Model\Timeline\Date::START, $iEndDate);
        }
        else {
            // start date of the first ticket in current project
            $aStartDate = array();
            $aDepends   = $this->getDepends();
            foreach ($aDepends as $iTicket) {
                $aStartDate[$iTicket] = (float) $this->_oBugzilla->getBugById($iTicket)->getStartDate($this->id());
            }

            asort($aStartDate);
            $this->_iStartDate = reset($aStartDate);
        }

        if (empty($this->_iStartDate) === true) {
            $this->_iStartDate = strtotime('+1 day ' . \Flightzilla\Model\Timeline\Date::START);
        }

        $this->_iStartDate = $this->_oDate->getNextWorkday($this->_iStartDate);

        return $this->_iStartDate;
    }

    /**
     * Get the end-date as timestamp.
     *
     * @return int
     */
    public function getEndDate() {

        if ($this->_iEndDate > 0) {
            return $this->_iEndDate;
        }

        if ($this->cf_due_date) {
            $sEndDate = (string) $this->cf_due_date;
            $this->_iEndDate = strtotime(str_replace('00:00:00', \Flightzilla\Model\Timeline\Date::END, $sEndDate));
        }

        if (empty($this->_iEndDate) or $this->_iEndDate < time()) {
            // End date of the last ticket in current project
            $aEndDates = array();
            $aDepends   = $this->getDepends();
            foreach ($aDepends as $iTicket) {
                $aEndDates[$iTicket] = (float) $this->_oBugzilla->getBugById($iTicket)->getEndDate();
            }

            asort($aEndDates);

            $this->_iEndDate = end($aEndDates);

            $this->_iEndDate = $this->_oDate->getNextWorkday($this->_iEndDate);
        }

        return $this->_iEndDate;
    }

    /**
     * Left hours of all dependencies
     *
     * @return float
     */
    public function getLeftTimeOfDependencies() {
        $fLeft = 0;
        $aDepends   = $this->getDepends();
        foreach ($aDepends as $iTicket) {
            $fLeft += (float) $this->_oBugzilla->getBugById($iTicket)->getLeftHours();
        }

        return $fLeft;
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
            try {
                if ($oTicket->isProject() === true) {
                    $this->_aDependentProjects[] = $oTicket->id();
                }
            }
            catch (\Exception $e) {
                /* happens, if a bug is not found, which is ok for closed bugs */
            }
        }

        return $this->_aDependentProjects;
    }

    /**
     * Are all depending tickets merged?
     *
     * @return boolean
     */
    public function isMerged() {

        $bReady = true;
        foreach ($this->getDependsAsStack() as $oTicket) {
            if ($oTicket->couldBeInTrunk() === false) {
                $bReady = false;
                break;
            }
        }

        return ($this->hasDevelopment() === true and $bReady === true and empty($this->_aDepends) === false);
    }

    /**
     * Does the project have any development-tickets?
     *
     * @return boolean
     */
    public function hasDevelopment() {
        if (is_null($this->_bOnlyConcepts) === true) {
            $this->_bOnlyConcepts = true;
            foreach ($this->getDependsAsStack() as $oTicket) {
                if ($oTicket->isConcept() === false) {
                    $this->_bOnlyConcepts = false;
                }
            }
        }

        return ($this->_bOnlyConcepts === false);
    }
}
