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
 * Enter a description ..
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Model_Project_Sorting {

    /**
     *
     */
    protected $_aStack = array();

    /**
     *
     */
    protected $_aSorted = array();

    /**
     *
     */
    protected $_aNotSortedStack = array();

    /**
     *
     */
    protected $_aGaps = array();

    /**
     *
     */
    protected $_bSorted = false;

    /**
     *
     */
    protected $_oBugzilla;

    /**
     * The human resource model
     *
     * @var Model_Resource_Manager
     */
    protected $_oResource;

    /**
     *
     */
    protected $_iDeadline;

    /**
     *
     */
    public function __construct(Model_Ticket_Source_Bugzilla $oBugzilla, Model_Resource_Manager $oResource, $iDeadline = null) {
        $this->_oBugzilla = $oBugzilla;
        $this->_oResource = $oResource;
        $this->_iDeadline = $iDeadline;
        $this->_aSorted = array();
    }

    /**
     * Add a bug
     *
     * @param  Model_Ticket_Type_Bug $oBug
     *
     * @return Model_Project_Sorting
     */
    public function add(Model_Ticket_Type_Bug $oBug) {
        $this->_aStack[$oBug->id()] = $oBug;
        $this->_bSorted = false;
        return $this;
    }

    /**
     * Get the sorted bugs
     *
     * @return array
     */
    public function getSortedBugs() {
        return $this->_sort();
    }

    /**
     * Sort the bugs
     *
     * @return array
     */
    protected function _sort() {
        if ($this->_bSorted !== true) {
            $this->_sortByStartDate();
//            if (empty($this->_aNotSortedStack) !== true) {
//                $this->_sortByPriority();
//            }
//
//            while ($this->_sortByDependency() === true) {
//                // do nothing
//            }

            $this->_bSorted = true;
//            if (empty($this->_aNotSortedStack) !== true) {
//                $oBug = reset($this->_aNotSortedStack);
//                throw new Model_Project_Sorting_DataException(sprintf(Model_Project_Sorting_DataException::INVALID_DATE, $oBug->id(), (string) $oBug->short_desc));
//            }
        }

        return $this->_aSorted;
    }

    /**
     *
     */
    protected function _findGaps() {
        $this->_aGaps = array();
        foreach ($this->_aSorted as $oBug) {
//            Zend_Debug::dump(date('r', $oBug->getStartDate()), __FILE__ . ':' . __LINE__);
//            Zend_Debug::dump($oBug->duration(), __FILE__ . ':' . __LINE__);
//            Zend_Debug::dump(date('r', $oBug->getEndDate()), __FILE__ . ':' . __LINE__);
        }

        return $this;
    }

    /**
     *
     */
    protected function _sortByDependency() {
        $bShiftedABug = false;

        $aSorted = array();
        foreach ($this->_aSorted as $oBug) {
            $aDepends = $oBug->getDepends($this->_oBugzilla);
            foreach ($aDepends as $iBug) {
                if (isset($aSorted[$iBug]) !== true) {
                    $oDepends = $this->_oBugzilla->getBugById($iBug);
                    $aSorted[$iBug] = $oDepends;

                    $bShiftedABug = true;
                }
            }

            if (isset($aSorted[$oBug->id()]) !== true) {
                $aSorted[$oBug->id()] = $oBug;
            }
        }

        foreach ($this->_aSorted as $oBug) {
            if (isset($this->_aNotSortedStack[$oBug->id()]) === true) {
                Zend_Debug::dump($oBug->id(), __FILE__ . ':' . __LINE__);
                unset($this->_aNotSortedStack[$oBug->id()]);
            }
        }

        $this->_aSorted = $aSorted;
        return $bShiftedABug;
    }

    /**
     *
     */
    protected function _sortByPriority() {
        $this->_findGaps();
        if (empty($this->_aGaps) !== true) {
            foreach ($this->_aNotSortedStack as $oBug) {
                foreach ($this->_aGaps as $aGap) {

                }
            }
        }

        return $this;
    }

    /**
     * Sort the bugs by start-date
     *
     * @return array
     */
    protected function _sortByStartDate() {
        $this->_aNotSortedStack = array();

        $aSort = array();
        foreach ($this->_aStack as $oBug) {
            $iStartDate = $oBug->getStartDate($this->_oBugzilla, $this->_oResource, $this->_iDeadline);
            if (empty($iStartDate) === true) {
                $this->_aNotSortedStack[$oBug->id()] = $oBug;
            }
            else {
                $aSort[$oBug->id()] = $iStartDate;
            }

        }

        asort($aSort);
        $this->_aSorted = array();
        foreach ($aSort as $iBug => $mStuff) {
            $this->_aSorted[$iBug] = $this->_aStack[$iBug];
        }

        unset($aSort);

        return $this;
    }
}