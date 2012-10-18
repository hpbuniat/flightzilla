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
namespace Flightzilla\Model\Project;


/**
 * Enter a description ..
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Sorting {

    /**
     * The ticket-stack
     *
     * @var \Flightzilla\Model\Ticket\Type\Bug[]
     */
    protected $_aStack = array();

    /**
     * Sorted tickets
     *
     * @var array
     */
    protected $_aSorted = array();

    /**
     * Tickets which are not yet sortable
     *
     * @var array
     */
    protected $_aNotSortedStack = array();

    /**
     * This stack was already sorted?
     *
     * @var boolean
     */
    protected $_bSorted = false;

    /**
     * The ticket-source
     *
     * @var \Flightzilla\Model\Ticket\Source\Bugzilla
     */
    protected $_oBugzilla;

    /**
     * Create the sorter
     *
     * @param \Flightzilla\Model\Ticket\Source\Bugzilla $oBugzilla
     */
    public function __construct(\Flightzilla\Model\Ticket\Source\Bugzilla $oBugzilla) {

        $this->_oBugzilla = $oBugzilla;
        $this->_aSorted = array();
    }

    /**
     * Add a bug
     *
     * @param  \Flightzilla\Model\Ticket\Type\Bug $oBug
     *
     * @return Sorting
     */
    public function add(\Flightzilla\Model\Ticket\Type\Bug $oBug) {

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
     *
     * @throws \Flightzilla\Model\Project\Sorting\DataException
     */
    protected function _sort() {

        if ($this->_bSorted !== true) {
            $this->_sortByStartDate();
            $this->_bSorted = true;
        }

        if (empty($this->_aNotSortedStack) !== true) {
            $oBug = reset($this->_aNotSortedStack);
            throw new \Flightzilla\Model\Project\Sorting\DataException(sprintf(\Flightzilla\Model\Project\Sorting\DataException::INVALID_DATE, $oBug->id(), $oBug->title()));
        }

        return $this->_aSorted;
    }

    /**
     * Sort tickets by dependency
     *
     * @return boolean
     */
    protected function _sortByDependency() {

        $bShiftedABug = false;

        $aSorted = array();
        foreach ($this->_aSorted as $oBug) {
            $aDepends = $oBug->getDepends();
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
     * Sort the bugs by start-date
     *
     * @return array
     */
    protected function _sortByStartDate() {

        $this->_aNotSortedStack = array();

        $aSort = array();
        foreach ($this->_aStack as $oBug) {
            $iStartDate = $oBug->getStartDate();
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
