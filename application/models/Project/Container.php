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
 * The container contains all projects and directs the interal sorting for each project
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Model_Project_Container {

    /**
     * The found projects
     *
     * @var Model_Ticket_Type_Bug[]
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
     * @var Model_Ticket_Source_Bugzilla
     */
    protected $_oBugzilla;

    /**
     * The resource model
     *
     * @var Model_Resource_Manager
     */
    protected $_oResource;

    /**
     * Found errors, while sorting the projects
     *
     * @var array
     */
    protected $_aErrors = array();

    /**
     * Create the container
     *
     * @param  Model_Ticket_Source_Bugzilla $oBugzilla
     */
    public function __construct(Model_Ticket_Source_Bugzilla $oBugzilla, Model_Resource_Manager $oResource) {
        $this->_oBugzilla = $oBugzilla;
        $this->_oResource = $oResource;
    }

    /**
     * Collect all projects
     *
     * @return Model_Project_Container
     */
    public function setup() {
        $aThemes = $this->_oBugzilla->getThemes();
        foreach ($aThemes as $oTheme) {
            if ($oTheme->isProject() === true and $oTheme->hasUnclosedBugs($this->_oBugzilla) === true) {
                $this->_aProjects[$oTheme->id()] = $oTheme;
            }
        }

        return $this;
    }

    /**
     *
     */
    public function sortThemes() {
        $this->_aOrderedProjects = array();
        foreach ($this->_aProjects as $oProject) {
            try {
                $oSort = new Model_Project_Sorting($oProject->getEndDate($this->_oBugzilla, $this->_oResource), $this->_oBugzilla);
                foreach ($oProject->getDepends($this->_oBugzilla) as $iBug) {
                    $oSort->add($this->_oBugzilla->getBugById($iBug));
                }

                $this->_aOrderedProjects[$oProject->id()] = $oSort->getSortedBugs();
                unset($oSort);
            }
            catch (Exception $e) {
                $this->_aErrors[] = $e->getMessage();
            }
        }

        return $this;
    }

    /**
     *
     */
    public function sortProjects() {

        $this->_aOrderedProjects = array();
        foreach ($this->_aProjects as $oProject) {
            try {
                $endDate = $oProject->getEndDate($this->_oBugzilla, $this->_oResource);
                $oSort = new Model_Project_Sorting($this->_oBugzilla, $this->_oResource, $endDate);
                foreach ($oProject->getDepends($this->_oBugzilla) as $iBug) {
                    $oSort->add($this->_oBugzilla->getBugById($iBug));
                }

                $this->_aOrderedProjects[$oProject->id()]['short_desc'] = $oProject->short_desc;
                $this->_aOrderedProjects[$oProject->id()]['tasks'] = $oSort->getSortedBugs();
                unset($oSort);
            }
            catch (Exception $e) {
                $this->_aErrors[] = $e->getMessage();
            }
        }
    }

    /**
     * Get the projects with ordered bugs
     *
     * @return array
     */
    public function getProjects() {
        return $this->_aOrderedProjects;
    }

    /**
     * Get the themes as stack
     *
     * @return array
     */
    public function getProjectsAsStack() {
        $aStack = array();
        foreach ($this->_aProjects as $oTheme) {
            $aStack[$oTheme->id()] = (string) $oTheme->short_desc;
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
}