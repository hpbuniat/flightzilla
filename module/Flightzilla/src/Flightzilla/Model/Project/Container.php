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
 * The container contains all projects and directs the interal sorting for each project
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Container {

    /**
     * The found projects
     *
     * @var \Flightzilla\Model\Ticket\Type\Bug[]
     */
    protected $_aProjects = array();

    /**
     * The projects with stacks of ordered bugs
     *
     * @var array
     */
    protected $_aOrderedProjects = array();

    /**
     * The Bugzilla-Model
     *
     * @var \Flightzilla\Model\Ticket\Source\Bugzilla
     */
    protected $_oBugzilla;

    /**
     * Found errors, while sorting the projects
     *
     * @var array
     */
    protected $_aErrors = array();

    /**
     * Gantt-Graph colors
     *
     * @var array
     */
    protected $_aColors = array(
        'ganttGreen',
        'ganttRed',
        'ganttOrange',
    );

    /**
     * Create the container
     *
     * @param  \Flightzilla\Model\Ticket\Source\Bugzilla $oBugzilla
     */
    public function __construct(\Flightzilla\Model\Ticket\Source\Bugzilla $oBugzilla) {

        $this->_oBugzilla = $oBugzilla;
    }

    /**
     * Collect all projects
     *
     * @return $this
     */
    public function setup() {

        $aProjects = $this->_oBugzilla->getProjects();
        foreach ($aProjects as $oProject) {
            if ($oProject->hasUnclosedBugs($this->_oBugzilla) === true) {
                $this->_aProjects[$oProject->id()] = $oProject;
            }
        }

        return $this;
    }

    /**
     * Sort the themes
     *
     * @return $this
     */
    public function sortThemes() {

        $this->_aOrderedProjects = array();
        foreach ($this->_aProjects as $oProject) {
            try {
                $oSort = new \Flightzilla\Model\Project\Sorting($this->_oBugzilla);
                foreach ($oProject->getDepends($this->_oBugzilla) as $iBug) {
                    $oSort->add($this->_oBugzilla->getBugById($iBug));
                }

                try {
                    $this->_aOrderedProjects[$oProject->id()] = $oSort->getSortedBugs();
                }
                catch (\Flightzilla\Model\Project\Sorting\DataException $e) {
                    $this->_aErrors[] = $e->getMessage();
                }

                unset($oSort);
            }
            catch (\Exception $e) {
                $this->_aErrors[] = $e->getMessage();
            }
        }

        return $this;
    }

    /**
     * Sort the projects
     *
     * @return $this
     */
    public function sortProjects() {

        $this->_aOrderedProjects = array();
        foreach ($this->_aProjects as $oProject) {
            try {
                $oSort = new \Flightzilla\Model\Project\Sorting($this->_oBugzilla);
                foreach ($oProject->getDepends() as $iBug) {
                    $oSort->add($this->_oBugzilla->getBugById($iBug));
                }

                $this->_aOrderedProjects[$oProject->id()]['project'] = $oProject;
                $this->_aOrderedProjects[$oProject->id()]['short_desc'] = $oProject->title();
                $this->_aOrderedProjects[$oProject->id()]['tasks'] = $oSort->getSortedBugs();
                unset($oSort);
            }
            catch (\Exception $e) {
                $this->_aErrors[] = sprintf('%s (Project: %d)', $e->getMessage(), $oProject->id());
            }
        }

        return $this;
    }

    /**
     * Get the projects with ordered bugs
     *
     * @param  boolean $bDetailed
     *
     * @return array
     */
    public function getProjects($bDetailed = false) {

        $bDetailed = (bool) $bDetailed;
        return $this->_createGanttData($bDetailed);
    }

    /**
     * Get the raw project-data
     *
     * @return array
     */
    public function getProjectsRaw() {

        return $this->_createIterator();
    }

    /**
     * Get the themes as stack
     *
     * @return array
     */
    public function getProjectsAsStack() {

        $aStack = array();
        foreach ($this->_aProjects as $oTheme) {
            $aStack[$oTheme->id()] = $oTheme->title();
        }

        return $aStack;
    }

    /**
     * Get the errors
     *
     * @return array
     */
    public function getErrors() {

        return $this->_aErrors;
    }

    /**
     * Create gantt-view-data
     *
     * @param  boolean $bDetailed
     *
     * @return array
     */
    protected function _createGanttData($bDetailed) {

        $iColor = $i = 0;
        $aProjects = array();
        foreach ($this->_aOrderedProjects as $aProject) {
            if (isset($aProject['tasks']) === true) {
                if ($iColor >= count($this->_aColors)) {
                    $iColor = 0;
                }

                $sColor = $this->_aColors[$iColor];
                $iColor++;
                if ($bDetailed === true) {
                    $bStillTheSameProject = false;
                    foreach ($aProject['tasks'] as $oTask) {
                        /* @var \Flightzilla\Model\Ticket\Type\Bug $oTask */

                        $aProjects[$i] = $this->_getGanttDetail($oTask, (false === $bStillTheSameProject) ? (string) $aProject['short_desc'] : ' ', $sColor);
                        $bStillTheSameProject = true;
                        $i++;
                    }
                }
                else {
                    $aProjects[$i] = $this->_getGanttDetail($aProject['project'], $aProject['short_desc'], $sColor);
                    $i++;
                }
            }
        }

        return $aProjects;
    }

    /**
     * Get the projects as iterateable data
     *
     * @return array
     */
    protected function _createIterator() {

        $aProjects = array();
        foreach ($this->_aOrderedProjects as $iTicket => $aProject) {
            if (isset($aProject['tasks']) === true) {
                $aStruct = array(
                    'name' => (string) $aProject['short_desc'],
                    'ticket' => $iTicket,
                    'tasks' => array()
                );
                foreach ($aProject['tasks'] as $oTask) {
                    $aStruct['tasks'][] = $oTask;
                }

                $aProjects[] = $aStruct;
            }
        }

        return $aProjects;
    }

    /**
     * Get the gantt-data for a ticket/project
     *
     * @param  \Flightzilla\Model\Ticket\Type\Bug $oTask
     * @param  string $sName
     * @param  string $sColor
     *
     * @return array
     */
    protected function _getGanttDetail(\Flightzilla\Model\Ticket\Type\Bug $oTask, $sName, $sColor) {

        return array(
            'name' => $sName,
            'desc' => (string) $oTask->id(),
            'values' => array(
                array(
                    'from' => '/Date(' . $oTask->getStartDate() * 1000 . ')/',
                    'to' => '/Date(' . $oTask->getEndDate() * 1000 . ')/',
                    'label' => $oTask->title(),
                    'customClass' => $sColor,
                    'desc' => '<b>' . $oTask->title() . '</b><br />'
                        . '<b>Assignee:</b> ' . (string) $oTask->getResource() . '<br />'
                        . '<b>Start:</b> ' . date('d.m.Y H:i', $oTask->getStartDate()) . '<br />'
                        . '<b>Ende:</b> ' . date('d.m.Y H:i', $oTask->getEndDate()) . '<br />'
                        . substr((string) $oTask->long_desc->thetext, 0, 120)
                )
            )
        );
    }
}
