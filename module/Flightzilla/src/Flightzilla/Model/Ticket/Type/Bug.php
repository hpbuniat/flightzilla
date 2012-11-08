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

use \Flightzilla\Model\Ticket\Type\Bug\Exception as BugException;

/**
 * A Ticket
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Bug extends \Flightzilla\Model\Ticket\AbstractType {

    /**
     * Bugzilla flags
     *
     * @var string
     */
    const FLAG_MERGE = 'MergeRequest';
    const FLAG_SCREEN = 'ScreenApproval';
    const FLAG_TESTSERVER = 'UpdateTestserver';
    const FLAG_TESTING = 'TestingRequest';
    const FLAG_TRANSLATION = 'TranslationRequest';
    const FLAG_DBCHANGE = 'DBChangeRequest_Live';
    const FLAG_DBCHANGE_TEST = 'DBChangeRequest_Test';
    const FLAG_COMMENT = 'CommentRequest';

    /**
     * @TODO: Move to configuration!
     */
    protected $_mappedFlags = array(
        self::FLAG_MERGE => 'flag_type-35',
        self::FLAG_SCREEN => 'flag_type-56',
        self::FLAG_TESTSERVER => 'flag_type-71',
        self::FLAG_TESTING => 'flag_type-30',
        self::FLAG_TRANSLATION => 'flag_type-122',
        self::FLAG_DBCHANGE => 'flag_type-112',
        self::FLAG_DBCHANGE_TEST => 'flag_type-113',
        self::FLAG_COMMENT => 'flag_type-21'
    );

    /**
     * Bugzilla status
     *
     * @var string
     */
    const STATUS_NEW = 'NEW';
    const STATUS_UNCONFIRMED = 'UNCONFIRMED';
    const STATUS_CONFIRMED = 'CONFIRMED';
    const STATUS_ASSIGNED = 'ASSIGNED';
    const STATUS_REOPENED = 'REOPENED';
    const STATUS_RESOLVED = 'RESOLVED';
    const STATUS_REVIEWED = 'REVIEWED';
    const STATUS_VERIFIED = 'VERIFIED';
    const STATUS_CLOSED = 'CLOSED';

    protected $_mappedStatus = array(
        self::STATUS_NEW         => 0,
        self::STATUS_UNCONFIRMED => 1,
        self::STATUS_CONFIRMED   => 2,
        self::STATUS_ASSIGNED    => 3,
        self::STATUS_REOPENED    => 4,
        self::STATUS_RESOLVED    => 5,
        self::STATUS_REVIEWED    => 6,
        self::STATUS_VERIFIED    => 7,
        self::STATUS_CLOSED      => 8
    );

    /**
     * Deadline status
     */
    const DEADLINE_PAST = 'long gone';
    const DEADLINE_TODAY = 'today';
    const DEADLINE_NEAR = 'near (within the week)';
    const DEADLINE_WEEK = 'next week';
    const DEADLINE_FAR = 'far away';

    /**
     * Workflow status
     *
     * @var string
     */
    const WORKFLOW_ESTIMATED = 'estimated';
    const WORKFLOW_ORGA = 'orga';
    const WORKFLOW_UNESTIMATED = 'unestimated';
    const WORKFLOW_INPROGRESS = 'working';
    const WORKFLOW_ACTIVE = 'Active';
    const WORKFLOW_TESTING = 'testing';
    const WORKFLOW_MERGE = 'mergeable';
    const WORKFLOW_DEADLINE = 'deadline';
    const WORKFLOW_SCREEN = 'screen';
    const WORKFLOW_COMMENT = 'comment';
    const WORKFLOW_FAILED = 'failed';
    const WORKFLOW_QUICK = 'quick';
    const WORKFLOW_TRANSLATION = 'i18n';
    const WORKFLOW_TIMEDOUT = 'timedout';

    /**
     * Components
     *
     * @var string
     */
    const COMPONENT_CONCEPT = 'Screens und Konzepte';

    /**
     * Different types
     *
     * @var string
     */
    CONST TYPE_STRING_BUG = 'MTB';
    CONST TYPE_STRING_THEME = 'Theme,Thema,Projekt';
    CONST TYPE_STRING_FEATURE = '';
    CONST TYPE_STRING_CONCEPT = 'Screen';

    CONST TYPE_BUG = 'bug';
    CONST TYPE_THEME = 'theme';
    CONST TYPE_FEATURE = 'feature';
    CONST TYPE_CONCEPT = 'screen';
    /**
     * The bug-types
     *
     * @var array
     */
    protected $_aTypes = array(
        self::TYPE_STRING_BUG => self::TYPE_BUG,
        self::TYPE_STRING_THEME  => self::TYPE_THEME,
        self::TYPE_STRING_FEATURE  => self::TYPE_FEATURE,
        self::TYPE_STRING_CONCEPT  => self::TYPE_CONCEPT,
    );

    /**
     * Bugzilla priorities
     */
    const PRIORITY_1 = 'P1';
    const PRIORITY_2 = 'P2';
    const PRIORITY_3 = 'P3';
    const PRIORITY_4 = 'P4';
    const PRIORITY_5 = 'P5';

    protected $_mappedPriorities = array(
        self::PRIORITY_1 => 5,
        self::PRIORITY_2 => 4,
        self::PRIORITY_3 => 3,
        self::PRIORITY_4 => 2,
        self::PRIORITY_5 => 1
    );

    /**
     * Bugzilla severities
     */
    const SEVERITY_BLOCKER     = 'Blocker';
    const SEVERITY_CRITICAL    = 'Critical';
    const SEVERITY_MAJOR       = 'Major';
    const SEVERITY_NORMAL      = 'Normal';
    const SEVERITY_MINOR       = 'Minor';
    const SEVERITY_TRIVIAL     = 'Trivial';
    const SEVERITY_ENHANCEMENT = 'Enhancement';
    const SEVERITY_IMPROVEMENT = 'Improvement';

    protected $_mappedSeverities = array(
        self::SEVERITY_BLOCKER     => 8,
        self::SEVERITY_CRITICAL    => 7,
        self::SEVERITY_MAJOR       => 6,
        self::SEVERITY_NORMAL      => 5,
        self::SEVERITY_MINOR       => 4,
        self::SEVERITY_TRIVIAL     => 3,
        self::SEVERITY_ENHANCEMENT => 2,
        self::SEVERITY_IMPROVEMENT => 1,
    );

    /**
     * The data-structure
     *
     * @var \SimpleXMLElement
     */
    protected $_data = null;

    /**
     * Flags of the Bug
     *
     * @var array
     */
    protected $_flags = null;

    /**
     * Bugs which a blocked by this bug
     *
     * @var array
     */
    protected $_aBlocks = array();

    /**
     * Known depend-bugs
     *
     * @var array
     */
    protected $_aDepends = array();

    /**
     * Data to persist, when sleeping
     *
     * @var string
     */
    protected $_sleep;

    /**
     * Cache for hasFlag
     *
     * @var array
     */
    protected $_aFlagCache = array();

    /**
     * This bugs theme
     *
     * @var int
     */
    protected $_iTheme = array();

    /**
     * The type of the bug
     *
     * @var string
     */
    protected $_sType;

    /**
     * The tickets keywords
     *
     * @var string
     */
    protected $_sKeywords = '';

    /**
     * Timestamp when the ticket starts.
     *
     * @var int
     */
    protected $_iStartDate = 0;

    /**
     * Timestamp for ticket's estimated end date.
     *
     * @var int
     */
    protected $_iEndDate = 0;

    /**
     * The ticket-id
     *
     * @var int
     */
    protected $_iId;

    /**
     * The ticket-source
     *
     * @var \Flightzilla\Model\Ticket\Source\Bugzilla
     */
    protected $_oBugzilla;

    /**
     * The resource manager
     *
     * @var \Flightzilla\Model\Resource\Manager
     */
    protected $_oResource;

    /**
     * The timeline, which keeps track of all necessary dates
     *
     * @var \Flightzilla\Model\Timeline\Date
     */
    protected $_oDate;

    /**
     * The tickets-status
     *
     * @var string
     */
    protected $_sStatus;

    /**
     * Create the bug
     *
     * @param \SimpleXMLElement $data
     */
    public function __construct(\SimpleXMLElement $data) {
        $this->_data = $data;
        $sName = strtok($data->assigned_to, '@');
        $aName = explode('.', strtoupper($sName));
        $this->_data->assignee_name = ucwords(preg_replace('!\W!', ' ', $sName));
        $this->_data->assignee_short = $aName[0]{0} . ((isset($aName[1]) === true) ? $aName[1]{0} : '');

        $this->_getFlags();
        $this->_getProperties();
    }

    /**
     * Send the bug to sleep ..
     *
     * @return array
     */
    public function __sleep() {
        $this->_sleep = $this->_data->asXML();
        return array('_sleep');
    }

    /**
     * Restore the bug
     *
     * @return void
     */
    public function __wakeup() {
        $this->_data = simplexml_load_string($this->_sleep);
        $this->_getFlags();
        $this->_getProperties();
    }

    /**
     * Inject some necessary objects
     *
     * @param \Flightzilla\Model\Ticket\Source\Bugzilla $oBugzilla
     * @param \Flightzilla\Model\Resource\Manager       $oResource
     * @param \Flightzilla\Model\Timeline\Date          $oDate
     */
    public function inject(\Flightzilla\Model\Ticket\Source\Bugzilla $oBugzilla, \Flightzilla\Model\Resource\Manager $oResource, \Flightzilla\Model\Timeline\Date $oDate) {
        $this->_oBugzilla = $oBugzilla;
        $this->_oResource = $oResource;
        $this->_oDate = $oDate;
    }

    /**
     * Set the theme
     *
     * @param  int $iTheme
     *
     * @return Bug
     */
    public function setTheme($iTheme) {
        $this->_iTheme = $iTheme;
        return $this;
    }

    /**
     * Get the theme
     *
     * @return int
     */
    public function getTheme() {
        return $this->_iTheme;
    }

    /**
     * Get the blocked bug-id
     *
     * @return array
     */
    public function blocks() {
        if (empty($this->_aBlocks) !== true) {
            return $this->_aBlocks;
        }

        $mDuplicate = $this->getDupe();
        if ($mDuplicate !== false) {
            $this->_aBlocks[] = (int) $mDuplicate;
        }

        if ($this->doesBlock() === true) {
            foreach ($this->_data->blocked as $iBlocked) {
                $this->_aBlocks[] = (int) $iBlocked;
            }
        }

        return $this->_aBlocks;
    }

    /**
     * Check if the bug is only a translation-bug
     *
     * @return boolean
     */
    public function isOnlyTranslation() {
        return ($this->hasFlag(Bug::FLAG_TRANSLATION) and $this->isOrga() === true);
    }

    /**
     * Check if a bug should last not very long
     *
     * @return boolean
     */
    public function isQuickOne() {
        $iEstimated = (int)  $this->estimated_time;
        return ($this->getStatus() !== Bug::STATUS_RESOLVED and $iEstimated > 0 and $iEstimated <= 3 and (int) $this->actual_time === 0);
    }

    /**
     * Check if a bug might be merged
     *
     * @return boolean
     */
    public function isMergeable() {
        $sStatus = $this->getStatus();
        return ($this->hasFlag(Bug::FLAG_TESTING,'?') !== true and ($this->hasFlag(Bug::FLAG_MERGE,'?') === true or (
            ($sStatus === Bug::STATUS_RESOLVED or $sStatus === Bug::STATUS_VERIFIED) and $this->hasFlag(Bug::FLAG_MERGE,'+') === false and $this->hasFlag(Bug::FLAG_TESTING,'+'))));
    }

    /**
     * Check if a bug has only failed testing-requests
     *
     * @return boolean
     */
    public function isFailed() {
        return ($this->hasFlag(Bug::FLAG_TESTING, '-') === true and $this->hasFlag(Bug::FLAG_TESTING,' +') !== true and $this->hasFlag(Bug::FLAG_TESTING, '?') !== true);
    }

    /**
     * Return the bugs deadline status
     *
     * @return string
     */
    public function deadlineStatus() {
        if ($this->cf_due_date) {
            $iDeadline = strtotime((string) $this->cf_due_date);
            $iDiff = ($iDeadline - time());
            $sToday = date('d.m.Y');
            $sDeadline = date('d.m.Y', $iDeadline);

            if ($iDiff < 0) {
                return Bug::DEADLINE_PAST;
            }
            elseif($sToday === $sDeadline) {
                return Bug::DEADLINE_TODAY;
            }
            elseif(date('W') === date('W', $iDeadline)) {
                return Bug::DEADLINE_NEAR;
            }
            elseif((date('W') + 1) === date('W', $iDeadline)) {
                return Bug::DEADLINE_WEEK;
            }
            elseif(date('W') === date('W', $iDeadline)) {
                return Bug::DEADLINE_FAR;
            }
        }

        return null;
    }

    /**
     * Get the deadline as human-readable string
     *
     * @return string
     */
    public function getDeadline() {
        if ($this->cf_due_date) {
            return date('d.m.Y', strtotime((string) $this->cf_due_date));
        }

        return null;
    }

    /**
     * Get the start-date in seconds
     *
     * @return int
     */
    public function getStartDate() {
        if ($this->_iStartDate > 0) {
            return $this->_iStartDate;
        }

        // is there a predecessor?
        $iPredecessor = $this->getActivePredecessor();
        if ($iPredecessor > 0) {
            $iEndDate = $this->_oBugzilla->getBugById($iPredecessor)->getEndDate();
            $this->_iStartDate = strtotime('+1 day ' . \Flightzilla\Model\Timeline\Date::START, $iEndDate);
        }
        // is there a deadline?
        elseif ($this->isEstimated() === true and $this->cf_due_date) {
            $iEndDate = $this->getEndDate();

            if (empty($iEndDate) !== true) {
                $this->_iStartDate = strtotime(sprintf('-%d day ' . \Flightzilla\Model\Timeline\Date::START, ceil($this->duration() / \Flightzilla\Model\Timeline\Date::AMOUNT)), $iEndDate);
            }
        }
        // is someone currently working on this ticket?
        elseif ($this->isWorkedOn(Bug::STATUS_CLOSED) === true) {
            $aWorked = $this->getWorkedHours();
            $iDays = floor($aWorked[0]['duration'] / \Flightzilla\Model\Timeline\Date::AMOUNT);
            $fHours = $aWorked[0]['duration'];
            if ($iDays > 0) {
                $fHours = ($aWorked[0]['duration'] - ($iDays * \Flightzilla\Model\Timeline\Date::AMOUNT));
            }

            $fMinutes = $fHours * 60;

            $sSign = '+';
            $sStartHour = sprintf('%s:00', \Flightzilla\Model\Timeline\Date::START);
            if ($this->isStatusAtLeast(Bug::STATUS_RESOLVED) === true) {
                // when a ticket is already closed, we can substract the worked time from the date, when it was entered
                $sSign = '-';
                $sStartHour = '';
            }

            $sTime = sprintf('-%d day %s %s%d minutes', $iDays, $sStartHour, $sSign, $fMinutes);
            $this->_iStartDate =  strtotime($sTime, $aWorked[0]['date']);
        }
        // has the human resource other tickets?
        else {
            $oResource = $this->getResource();
            if ($oResource instanceof \Flightzilla\Model\Resource\Human) {
                $nextPrioBug = $oResource->getNextHigherPriorityTicket($this);
                if ($nextPrioBug->id() !== $this->id()) {
                    $this->_iStartDate = $nextPrioBug->getEndDate();
                }
            }
        }

        if ($this->_iStartDate === 0) {
            $oProject = $this->_oBugzilla->getProject($this);
            if ($oProject instanceof \Flightzilla\Model\Ticket\Type\Project) {
                $this->_iStartDate = $oProject->getStartDate();
            }
        }

        if ($this->_iStartDate === 0){
            $this->_iStartDate = strtotime('tomorrow ' . \Flightzilla\Model\Timeline\Date::START);
        }

        $this->_iStartDate = $this->_oDate->getNextWorkday($this->_iStartDate);
        return $this->_iStartDate;
    }

    /**
     * Check, if a ticket was changed within a given time-limit
     *
     * @param $iLimit
     *
     * @return boolean
     */
    public function isChangedWithinLimit($iLimit) {
        return ((time() - $this->getLastActivity()) < $iLimit);
    }

    /**
     * Get the end-date in seconds
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
        elseif ($this->isStatusAtLeast(Bug::STATUS_RESOLVED) === true) {
            $aWorked = $this->getWorkedHours();
            $aLast = end($aWorked);

            $this->_iEndDate = $aLast['date'];
        }
        else {
            // Start date + estimated
            $iStartDate      = $this->getStartDate();
            $iDays           = ceil($this->duration() / \Flightzilla\Model\Timeline\Date::AMOUNT);

            $this->_iEndDate = strtotime(sprintf('+%d day ' . \Flightzilla\Model\Timeline\Date::END, $iDays), $iStartDate);
            $this->_iEndDate = $this->_oDate->getNextWorkday($this->_iEndDate);
        }

        return $this->_iEndDate;
    }

    /**
     * Get the tickets duration
     *
     * @return float
     */
    public function duration() {
        if ($this->isEstimated()) {
            return (float) $this->estimated_time;
        }

        return 0;
    }

    /**
     * Get the bug-title
     *
     * @return string
     */
    public function title() {
        return (string) $this->_data->short_desc;
    }

    /**
     * Get the bug-id
     *
     * @return int
     */
    public function id() {
        return $this->_iId;
    }

    /**
     * Check if a bugs blocks another one
     *
     * @return boolean
     */
    public function doesBlock() {
        if (isset($this->_data->blocked) === true) {
            return true;
        }

        return false;
    }

    /**
     * Does the ticket depends on other tickets?
     *
     * @return bool
     */
    public function hasDependencies(){
        return (isset($this->dependson) === true);
    }

    /**
     * Get some properties for the ticket
     * - the type
     * - the id
     *
     * @param Bug        $oBug
     *
     * @return bool
     */
    public function doesDependOn(Bug $oBug){
        if ($this->hasDependencies()) {
            $aDependencies = $this->getDepends();
            foreach ($aDependencies as $iTicket) {
                if ($oBug->id() === $this->_oBugzilla->getBugById($iTicket)->id()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determine the type of the bug
     *
     * @return Bug
     */
    protected function _getProperties() {
        $this->_iId = (int) $this->_data->bug_id;
        $this->_sKeywords = (string) $this->_data->keywords;
        $this->_sStatus = (string) $this->bug_status;

        $this->_sType = '';
        $sTitle = $this->title();
        foreach ($this->_aTypes as $sKeywords => $sType) {
            $aKeywords = explode(',', $sKeywords);
            if (empty($aKeywords) !== true) {
                foreach ($aKeywords as $sKeyword) {
                    if (empty($sKeyword) !== true) {
                        if (stristr($sTitle, $sKeyword) !== false or $this->hasKeyword($sKeyword) === true) {
                            $this->_sType = $sType;
                        }
                    }
                }
            }

            unset($aKeywords);
        }

        unset($sTitle);

        if (empty($this->_sType) === true) {
            $this->_sType = ($this->isConcept() === true) ? self::TYPE_CONCEPT : self::TYPE_FEATURE;
        }

        return $this;
    }

    /**
     * Check if a bug is a given type
     *
     * @param  string $sType
     *
     * @return boolean
     */
    public function isType($sType) {
        return ($this->_sType === $sType);
    }

    /**
     * Get the type
     *
     * @return string
     */
    public function getType() {
        return $this->_sType;
    }

    /**
     * Check if the bug is a theme
     *
     * @return boolean
     */
    public function isTheme() {
        return $this->hasKeyword('theme');
    }

    /**
     * Check if the bug is a theme
     *
     * @return boolean
     */
    public function isConcept() {
        return (string) $this->component === self::COMPONENT_CONCEPT;
    }

    /**
     * Get the bugs component
     *
     * @return string
     */
    public function getComponent() {
        return (string) $this->component;
    }

    /**
     * Check if a bug is a project
     *
     * @return boolean
     */
    public function isProject() {
        return $this->hasKeyword('Projekt');
    }

    /**
     * Check if the bug has been estimated
     *
     * @return bool
     */
    public function isEstimated() {
        return (bool) ($this->estimated_time > 0);
    }

    /**
     * Check if the bug has been worked on
     *
     * @param  string $sStatusFilter
     *
     * @return bool
     */
    public function isWorkedOn($sStatusFilter = Bug::STATUS_ASSIGNED) {
        return ($this->isEstimated() and (bool) ($this->actual_time > 0) and $this->isStatusAtMost($sStatusFilter) === true);
    }

    /**
     * Check, if the bug is active & wip
     *
     * @return boolean
     */
    public function isWip() {
        return ($this->isTheme() === false and $this->isOrga() === false and $this->isConcept() === false and $this->getStatus() === Bug::STATUS_ASSIGNED);
    }

    /**
     * Check, if someone is working on this bug
     *
     * @return bool
     */
    public function isActive() {
        return ($this->getStatus() === Bug::STATUS_ASSIGNED);
    }

    /**
     * Check if the ticket is already confirmed.
     *
     * @return bool
     */
    public function isConfirmed() {
        return ($this->getStatus() === Bug::STATUS_CONFIRMED);
    }

    /**
     * Check, if the bug is of type organisation
     */
    public function isOrga() {
        return $this->hasKeyword('organisation');
    }

    /**
     * Check if a keyword exists
     *
     * @param  string $sKeyword
     *
     * @return boolean
     */
    public function hasKeyword($sKeyword) {
        if (stripos($this->_sKeywords, $sKeyword) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Get the depend-bugs
     *
     * @return array
     */
    public function getDepends() {
        if (empty($this->_aDepends) === true) {
            $this->hasUnclosedBugs();
        }

        return $this->_aDepends;
    }

    /**
     * Return the ticket number of the tickets predecessor or 0 if there isn't one.
     *
     * A valid predecessor has the status unconfirmed, confirmed or assigned, and is not a project or theme.
     * If there are more than one predecessor, the one with the latest end will be returned.
     *
     * @return int
     */
    public function getActivePredecessor() {
        if ($this->hasDependencies()) {

            $aEndDates = array();
            $dependencies = $this->getDepends($this->_oBugzilla);

            if (count($dependencies) > 1) {
                foreach ($dependencies as $dependency) {
                    $oTicket = $this->_oBugzilla->getBugById($dependency);
                    if (($oTicket->isStatusAtMost(Bug::STATUS_REOPENED)
                            and $oTicket->isTheme() === false
                            and $oTicket->isProject() === false)
                    ) {

                        $aEndDates[$oTicket->id()] = $oTicket->getEndDate();
                    }
                }

                if (empty($aEndDates) === true) {
                    return 0;
                }

                arsort($aEndDates);
                return key($aEndDates);
            }
            else {
                $iDepends = (int) reset($dependencies);
                $oTicket = $this->_oBugzilla->getBugById($iDepends);
                if ($oTicket->isTheme() === false
                    and $oTicket->isProject() === false
                    and $oTicket->isStatusAtMost(Bug::STATUS_REOPENED)
                ) {

                    return $iDepends;
                }
            }
        }

        return 0;
    }

    /**
     * Determine, if the theme has unclosed dependencies
     *
     * @return boolean
     */
    public function hasUnclosedBugs() {
        $bReturn = false;
        if (isset($this->dependson) === true) {
            foreach ($this->dependson as $iBug) {
                try {
                    $iBug = (int) $iBug;
                    $oBug = $this->_oBugzilla->getBugById($iBug);
                    if ($oBug->isProject() === false and $oBug->isTheme() === false) {
                        if ($oBug->isClosed() !== true) {
                            $bReturn = true;
                        }

                        $this->_aDepends[] = $iBug;
                    }
                }
                catch (\Exception $e) {
                    /* happens, if a bug is not found, which is ok for closed bugs */
                }
            }
        }

        return $bReturn;
    }

    /**
     * Check if the bug is closed
     *
     * @return boolean
     */
    public function isClosed() {
        if ($this->getStatus() !== Bug::STATUS_CLOSED) {
            return false;
        }

        return true;
    }

    /**
     * Check, if a bug was merged
     *
     * @return boolean
     */
    public function isMerged() {
        return ($this->isClosed() === true or $this->isTheme() === true or ($this->hasFlag(Bug::FLAG_MERGE, '+') === true and $this->hasFlag(Bug::FLAG_MERGE, '?') === false));
    }

    /**
     * Return true, if this bug could have already been merged, due its dependencies
     *
     * @return boolean
     */
    public function couldBeInTrunk() {
        return ($this->isMerged() === true or $this->getDupe() !== false or ($this->hasFlag(Bug::FLAG_SCREEN, '+') === true and $this->doesBlock() === true));
    }

    /**
     * Get the dupe-id or false, if there is none
     *
     * @return mixed
     */
    public function getDupe() {
        if ($this->getStatus() === Bug::STATUS_RESOLVED and isset($this->_data->dup_id) === true) {
            return $this->_data->dup_id;
        }

        return false;
    }

    /**
     * Get and normalize the flags
     *
     * @return Bug
     */
    private function _getFlags() {
        $flags = array();
        if (isset($this->_data->flag) === true) {
            foreach ($this->_data->flag as $flag) {
                $flag = $flag->attributes();

                $sName = (string) $flag['name'];
                $sName = str_replace(array(
                    'fluege.de-',
                    'fluege.de',
                    'Flug-'
                ), '', $sName);
                $sName = preg_replace('/\W/', '', $sName);

                $aFlag = array(
                    'name' => (string) $sName,
                    'id' =>  (int) $flag['id'],
                    'type_id' => (int) $flag['type_id'],
                    'status' => (string) $flag['status'],
                    'setter' => (string) $flag['setter']
                );

                if (isset($flag['requestee']) === true) {
                    $sUser = strtok($flag['requestee'], '@');
                    $aName = explode('.', strtoupper($sUser));
                    $this->_data->{strtolower($sName) . '_user'} = $aName[0]{0} . ((isset($aName[1]) === true) ? $aName[1]{0} : '');
                    $aFlag['requestee'] = $sUser;
                    $aFlag['requestee_mail'] = $flag['requestee'];
                    $aFlag['requestee_short'] = $this->_data->{strtolower($sName) . '_user'};
                }

                $flags[] = $aFlag;
            }
        }

        $this->_flags = $flags;
        return $this;
    }

    /**
     * Get all flags
     *
     * @return array
     */
    public function getFlags() {
        return $this->_flags;
    }

    /**
     * Get the name of a flag for the update-payload
     *
     * @param  string $sFlag Name of the Flag
     * @param  string $sFilter Filter a specific status
     *
     * @return string
     */
    public function getFlagName($sFlag, $sFilter = '') {
        $iCount = 0;
        $aMatchingFlag = '';
        foreach ($this->_flags as $aFlag) {
            if ($aFlag['name'] === $sFlag) {
                $iCount++;

                if (empty($sFilter) === true or $aFlag['status'] === $sFilter) {
                    $aMatchingFlag = $aFlag;
                }
            }
        }

        $sName = '';
        if (empty($aMatchingFlag) === true) {
            $sName = $this->_mappedFlags[$sFlag];
        }
        else {
            $sName = ($iCount > 0) ? sprintf('flag-%d', $aMatchingFlag['id']) : sprintf('flag_type-%d', $aMatchingFlag['type_id']);
        }

        return $sName;
    }

    /**
     * Check, if a flag exists
     * - optionally compare the value with $value
     * - optionally compare the assignee with $sRequestee
     *
     * @param  string $key
     * @param  string $value
     * @param  string $sRequestee
     *
     * @return boolean
     */
    public function hasFlag($key = null, $value = null, $sRequestee = null) {
        if (empty($this->_flags) === true) {
            return false;
        }

        $sHash = md5($key . $value . $sRequestee);
        if (isset($this->_aFlagCache[$sHash]) === true) {
            return $this->_aFlagCache[$sHash];
        }

        $return = false;
        foreach ($this->_flags as $aFlag) {
            if (isset($sAssignee) === true) {
                if ($aFlag['requestee_mail'] === $sRequestee and $aFlag['name'] == $key and $aFlag['status'] == $value) {
                    $return = true;
                }
            }
            else {
                if (isset($value) === true and $aFlag['name'] == $key and $aFlag['status'] == $value) {
                    $return = true;
                }
                elseif (isset($value) === false and $aFlag['name'] == $key) {
                    $return = true;
                }
            }
        }

        $this->_aFlagCache[$sHash] = $return;
        return $return;
    }

    /**
     * Get the status
     *
     * @return string
     */
    public function getStatus() {
        return $this->_sStatus;
    }

    /**
     * Get the reporter
     *
     * @return string
     */
    public function getReporter() {
        return $this->_data->reporter;
    }

    /**
     * Get the worked ours
     *
     * @return array
     */
    public function getWorkedHours() {
        $aHistory = $this->_data->xpath('long_desc');
        $aTimes = array();
        foreach ($aHistory as $oItem) {
            if (isset($oItem->work_time) === true) {
                $sResource = $this->_oResource->getResourceByEmail((string) $oItem->who);
                $aTimes[] = array(
                    'date' => strtotime((string) $oItem->bug_when),
                    'duration' => (float) $oItem->work_time,
                    'user' => $sResource,
                    'ticket' => $this->id()
                );
            }
        }

        unset($aHistory);

        return $aTimes;
    }

    /**
     * Get the first date, when someone worked on this bug
     *
     * @return int
     */
    public function getFirstWorkDate() {
        $iTimestamp = null;
        $aTimes = $this->getWorkedHours();
        if (empty($aTimes) !== true) {
            $iTimestamp = $aTimes[0]['date'];
        }

        return $iTimestamp;
    }

    /**
     * Get the date of the last-activity
     *
     * @return int
     */
    public function getLastActivity() {
        return strtotime($this->_data->delta_ts);
    }

    /**
     * Check if a property exists
     *
     * @param  string $key
     *
     * @return boolean
     */
    public function __isset($key) {
        return isset($this->_data->$key);
    }

    /**
     * Magic getter
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function __get($key) {
        if (isset($this->_data->$key) === true) {
            return $this->_data->$key;
        }
        else {
            return null;
        }
    }

    /**
     * String-cast
     *
     * @return string
     */
    public function __toString() {
        return $this->id();
    }

    /**
     * Get the assignee (parsed)
     *
     * @return string
     */
    public function getAssignee(){
        return (string) $this->_data->assigned_to;
    }

    /**
     * Get the resource
     *
     * return \Flightzilla\Model\Resource\Human | string
     */
    public function getResource() {
        $sRealName = null;
        $sAssignee = $this->getAssignee();
        try {
            $sRealName = $this->_oResource->getResourceByEmail($sAssignee);
            return ($sRealName === $sAssignee) ? $sAssignee : $this->_oResource->getResource($sRealName);
        }
        catch (\InvalidArgumentException $e) {
            return $sAssignee;
        }
    }

    /**
     * Get the priority
     *
     * @param  boolean $bMapped
     *
     * @return string
     */
    public function getPriority($bMapped = false){
        if ($bMapped === true) {
            return $this->_mappedPriorities[(string) $this->_data->priority];
        }

        return (string) $this->_data->priority;
    }

    /**
     * Get the severity
     *
     * @param  boolean $bMapped
     *
     * @return string
     */
    public function getSeverity($bMapped = false){
        if ($bMapped === true) {
            return $this->_mappedSeverities[(string) $this->_data->bug_severity];
        }

        return (string) $this->_data->bug_severity;
    }

    /**
     * Check, if a status is at least $sComparisonStatus
     *
     * @param  string $sComparisonStatus
     *
     * @throws BugException
     *
     * @return bool
     */
    public function isStatusAtLeast($sComparisonStatus) {

        if (isset($this->_mappedStatus[$sComparisonStatus]) === false){
            throw new BugException(sprintf(BugException::INVALID_STATUS, $sComparisonStatus));
        }

        return ($this->_mappedStatus[$this->getStatus()] >= $this->_mappedStatus[$sComparisonStatus]);
    }

    /**
     * Check, if a status is at most $sComparisonStatus
     *
     * @param  string $sComparisonStatus
     *
     * @throws BugException
     *
     * @return bool
     */
    public function isStatusAtMost($sComparisonStatus){

        if (isset($this->_mappedStatus[$sComparisonStatus]) === false){
            throw new BugException(sprintf(BugException::INVALID_STATUS, $sComparisonStatus));
        }

        return ($this->_mappedStatus[$this->getStatus()] <= $this->_mappedStatus[$sComparisonStatus]);
    }
}
