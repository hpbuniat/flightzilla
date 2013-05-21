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

use Flightzilla\Model\Ticket\Type\Bug\Exception as BugException;
use Flightzilla\Model\Ticket\Source\Bugzilla;
use Flightzilla\Model\Timeline\Date;

/**
 * A Ticket
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
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
     * Flag user-fields
     */
    const FLAG_USER_SETTER = 'setter';
    const FLAG_USER_REQUESTEE = 'requestee_mail';

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
    const STATUS_VERIFIED = 'VERIFIED';
    const STATUS_CLOSED = 'CLOSED';

    protected $_mappedStatus = array(
        self::STATUS_NEW         => 0,
        self::STATUS_UNCONFIRMED => 1,
        self::STATUS_CONFIRMED   => 2,
        self::STATUS_ASSIGNED    => 3,
        self::STATUS_REOPENED    => 4,
        self::STATUS_RESOLVED    => 5,
        self::STATUS_VERIFIED    => 6,
        self::STATUS_CLOSED      => 7
    );

    const RESOLUTION_FIXED = 'FIXED';
    const RESOLUTION_REVIEWED = 'REVIEWED';

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
    CONST TYPE_STRING_PROJECT = 'Projekt';
    CONST TYPE_STRING_FEATURE = '';
    CONST TYPE_STRING_CONCEPT = 'Screen';

    CONST TYPE_BUG = 'bug';
    CONST TYPE_THEME = 'theme';
    CONST TYPE_PROJECT = 'project';
    CONST TYPE_FEATURE = 'feature';
    CONST TYPE_CONCEPT = 'screen';

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
     * @var Bugzilla
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
     * @var Date
     */
    protected $_oDate;

    /**
     * The tickets-status
     *
     * @var string
     */
    protected $_sStatus;

    /**
     * The active predecessor-ticket-number
     *
     * @var int
     */
    protected $_iPredecessor;

    /**
     * Is this ticket a container (project/theme)
     *
     * @var boolean
     */
    protected $_bContainer = false;

    /**
     * The worked-hours
     *
     * @var array
     */
    protected $_aWorked = array();

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
     * Check, if there was an error retrieving the ticket
     *
     * @return boolean
     */
    public function isError() {
        return (empty($this->_data['error']) === false);
    }

    /**
     * Inject some necessary objects
     *
     * @param Bugzilla $oBugzilla
     * @param \Flightzilla\Model\Resource\Manager       $oResource
     * @param Date          $oDate
     */
    public function inject(Bugzilla $oBugzilla, \Flightzilla\Model\Resource\Manager $oResource, Date $oDate) {
        $this->_oBugzilla = $oBugzilla;
        $this->_oResource = $oResource;
        $this->_oDate = $oDate;
    }

    /**
     * Get the ticket-service
     *
     * @return Bugzilla
     */
    public function getTicketService() {
        return $this->_oBugzilla;
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
     * Get the projects, this ticket might be part of
     *
     * @return array
     */
    public function getProjects() {
        $aProjects = array();
        foreach ($this->blocks() as $iBlocked) {

            $oBlocked = $this->_oBugzilla->getBugById($iBlocked);
            /* @var $oBlocked Bug */

            if ($oBlocked->isProject() === true) {
                $aProjects[$oBlocked->id()] = $oBlocked;
            }
        }

        return $aProjects;
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
     * Check if a bug has only failed testing-requests
     *
     * @return boolean
     */
    public function isFailed() {
        return ($this->hasFlag(Bug::FLAG_TESTING, Bugzilla::BUG_FLAG_DENIED) === true and $this->hasFlag(Bug::FLAG_TESTING, Bugzilla::BUG_FLAG_GRANTED) !== true and $this->hasFlag(Bug::FLAG_TESTING, Bugzilla::BUG_FLAG_REQUEST) !== true);
    }

    /**
     * Return the bugs deadline status
     *
     * @return string
     */
    public function deadlineStatus() {
        $sDeadlineStatus = '';

        $sDeadline = $this->getDeadline();
        if (empty($sDeadline) !== true) {
            $iDeadline = strtotime($sDeadline);
            $iDiff = ($iDeadline - time());
            $sToday = date('d.m.Y');
            $sDeadline = date('d.m.Y', $iDeadline);

            if ($iDiff < 0) {
                $sDeadlineStatus = Bug::DEADLINE_PAST;
            }
            elseif($sToday === $sDeadline) {
                $sDeadlineStatus = Bug::DEADLINE_TODAY;
            }
            elseif(date('W') === date('W', $iDeadline)) {
                $sDeadlineStatus = Bug::DEADLINE_NEAR;
            }
            elseif((((int) date('W')) + 1) === date('W', $iDeadline)) {
                $sDeadlineStatus = Bug::DEADLINE_WEEK;
            }
            elseif(date('W') === date('W', $iDeadline)) {
                $sDeadlineStatus = Bug::DEADLINE_FAR;
            }
        }

        if (empty($sDeadlineStatus) === true) {
            $aWeeks = $this->_oDate->getWeeks();
            $sWeek = $this->getWeek();
            foreach ($aWeeks as $sWeekAlias => $aWeek) {
                if ($aWeek['title'] === $sWeek) {
                    switch ($sWeekAlias) {
                        case Date::WEEK_PREVIOUS:
                            $sDeadlineStatus = Bug::DEADLINE_PAST;
                            break;

                        case Date::WEEK_CURRENT:
                            $sDeadlineStatus = (date('w') < 4) ? Bug::DEADLINE_NEAR : Bug::DEADLINE_TODAY;
                            break;

                        case Date::WEEK_NEXT:
                            $sDeadlineStatus = Bug::DEADLINE_WEEK;
                            break;

                        case Date::WEEK_NEXT_BUT_ONE:
                            $sDeadlineStatus = Bug::DEADLINE_FAR;
                            break;
                    }
                }
            }
        }

        return $sDeadlineStatus;
    }

    /**
     * Get the deadline as human-readable string
     *
     * @return string|boolean
     */
    public function getDeadline() {
        $sDeadline = false;
        if (empty($this->cf_due_date) !== true) {
            $sDeadline = date('d.m.Y', strtotime((string) $this->cf_due_date));
        }
        elseif ($this->getWeek() !== false) {
            $sDeadline = date('d.m.Y', $this->_oDate->getDateFromWeek($this->getWeek()));
        }

        return $sDeadline;
    }

    /**
     * Get the start-date in seconds
     *
     * @param  boolean|null $iCalled
     *
     * @return int
     */
    public function getStartDate($iCalled = null) {
        if ($this->_iStartDate > 0) {
            return $this->_iStartDate;
        }

        // is there a predecessor?
        $iPredecessor = $this->getActivePredecessor();
        if (empty($iPredecessor) !== true) {
            $iEndDate = $this->_oBugzilla->getBugById($iPredecessor)->getEndDate($iCalled);
            $this->_iStartDate = strtotime('+1 day ' . Date::START, $iEndDate);
        }
        // is there a deadline?
        elseif ($this->isEstimated() === true and $this->getDeadline() !== false) {
            $iEndDate = $this->getEndDate($iCalled);

            if (empty($iEndDate) !== true) {
                $this->_iStartDate = strtotime(sprintf('-%d day ' . Date::START, ceil($this->getLeftHours() / Date::AMOUNT)), $iEndDate);
            }
        }
        // is someone currently working on this ticket?
        elseif ($this->isWorkedOn(Bug::STATUS_CLOSED) === true) {
            $aWorked = $this->getWorkedHours();
            $iDays = floor($aWorked[0]['duration'] / Date::AMOUNT);
            $fHours = $aWorked[0]['duration'];
            if ($iDays > 0) {
                $fHours = ($aWorked[0]['duration'] - ($iDays * Date::AMOUNT));
            }

            $fMinutes = $fHours * 60;

            $sSign = '+';
            $sStartHour = sprintf('%s:00', Date::START);
            if ($this->isStatusAtLeast(Bug::STATUS_RESOLVED) === true) {
                // when a ticket is already closed, we can subtract the worked time from the date, when it was entered
                $sSign = '-';
                $sStartHour = '';
            }

            $sTime = sprintf('-%d day %s %s%d minutes', $iDays, $sStartHour, $sSign, $fMinutes);
            $this->_iStartDate =  strtotime($sTime, $aWorked[0]['datetime']);
        }
        // has the human resource other tickets?
        else {
            $oResource = $this->getResource();
            if ($oResource instanceof \Flightzilla\Model\Resource\Human) {
                $nextPrioBug = $oResource->getNextHigherPriorityTicket($this);

                if ($nextPrioBug->id() !== $this->id()) {
                    $this->_iStartDate = $nextPrioBug->getEndDate($iCalled);
                }
            }
        }

        if (empty($this->_iStartDate) === true) {
            $oProject = $this->_oBugzilla->getProject($this);
            if ($oProject instanceof \Flightzilla\Model\Ticket\Type\Project and $oProject->id() !== $iCalled and $this->id() !== $iCalled) {
                $this->_iStartDate = $oProject->getStartDate($this->id());
            }
        }

        if (empty($this->_iStartDate) === true) {
            $this->_iStartDate = strtotime('+1 day ' . Date::START);
        }

        $this->_iStartDate = $this->_oDate->getNextWorkday($this->_iStartDate);
        if ($this->getEndDate($iCalled) < $this->_iStartDate) {
            $this->_oBugzilla->getLogger()->info(sprintf('End-Date < Start-Date! -> Ticket: %s, Start: %s, End: %s', $this->id(), date('Y-m-d', $this->_iStartDate), date('Y-m-d', $this->getEndDate())));
        }

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
     * @param  boolean|null $iCalled
     *
     * @return int
     */
    public function getEndDate($iCalled = null) {

        if ($this->_iEndDate > 0) {
            return $this->_iEndDate;
        }

        $iTime = time();
        $bIsResolved = ($this->isStatusAtLeast(Bug::STATUS_RESOLVED) === true);

        // if the ticket is resolved, try to get the last date of work
        if ($bIsResolved === true) {
            $aWorked = $this->getWorkedHours();
            $aLast = end($aWorked);

            $this->_iEndDate = $aLast['datetime'];
        }

        $sDeadline = $this->getDeadline();
        if (empty($this->_iEndDate) === true and empty($sDeadline) !== true) {
            $this->_iEndDate = strtotime(str_replace('00:00:00', Date::END, $sDeadline));
        }
        elseif ($this->getWeek() !== false) {
            $this->_iEndDate = $this->_oDate->getDateFromWeek($this->getWeek());
        }
        elseif($this->isOrga() === true) {
            // if the ticket is of type 'organization' and has no deadline, then it is finished right now --> @see \Flightzilla\Model\Ticket\Integrity\Constraint\OrganizationWithoutDue
            $this->_iEndDate = $iTime;
        }

        if ($bIsResolved === false and (empty($this->_iEndDate) === true or $this->_iEndDate < $iTime)) {
            // Start date + estimated
            if ($this->isWorkedOn(Bug::STATUS_CLOSED) === true) {
                $iLeft = $this->getLeftHours();
                if ($iLeft <= 0) {
                    $iLeft = Date::AMOUNT;
                }

                $iStartDate = time();
                if ($this->getStatus() !== self::STATUS_ASSIGNED) {
                    $iStartDate = $this->_oDate->getNextWorkday($iStartDate);
                }
            }
            else {
                $iStartDate  = $this->getStartDate($iCalled);
                $iLeft       = $this->getLeftHours();
            }

            $iDays           = ceil($iLeft / Date::AMOUNT);

            $this->_iEndDate = strtotime(sprintf('+%d day ' . Date::END, $iDays), $iStartDate);
            $this->_iEndDate = $this->_oDate->getNextWorkday($this->_iEndDate);
        }

        if ($this->_iEndDate < $this->getStartDate($iCalled)) {
            $this->_oBugzilla->getLogger()->info(sprintf('End-Date < Start-Date! -> Ticket: %s, Start: %s, End: %s', $this->id(), date('Y-m-d', $this->getStartDate()), date('Y-m-d', $this->_iEndDate)));
        }

        return $this->_iEndDate;
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
     * Get the release-note
     *
     * @return string
     */
    public function getReleaseNote() {
        return (empty($this->_data->cf_releasenote) !== true) ? (string) $this->_data->cf_releasenote : $this->title();
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
        $aDepends = $this->getDepends();
        return (empty($aDepends) === false);
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

        $this->_sType = \Flightzilla\Model\Ticket\Type::getType($this->_data, $this->title(), $this->_sKeywords);
        if (empty($this->_sType) === true) {
            $sSeverity = $this->getSeverity();
            if ($sSeverity !== self::SEVERITY_ENHANCEMENT and $sSeverity !== self::SEVERITY_IMPROVEMENT) {
                $this->_sType = self::TYPE_BUG;
            }
        }

        if (empty($this->_sType) === true) {
            $this->_sType = ($this->isConcept() === true) ? self::TYPE_CONCEPT : self::TYPE_FEATURE;
        }

        $this->_bContainer = ($this->isTheme() or $this->isProject());

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
        return $this->isType(self::TYPE_THEME);
    }

    /**
     * Check if a bug is a project
     *
     * @return boolean
     */
    public function isProject() {
        return $this->isType(self::TYPE_PROJECT);
    }

    /**
     * Is this ticket a ticket-container
     *
     * @return boolean
     */
    public function isContainer() {
        return $this->_bContainer;
    }

    /**
     * Check if the bug is a theme
     *
     * @return boolean
     */
    public function isConcept() {
        return ($this->getComponent() === self::COMPONENT_CONCEPT);
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
     * Get the version
     *
     * @return string
     */
    public function getVersion() {
        $sVersion = (string) $this->version;
        return ($sVersion !== 'unspecified') ? $sVersion : '';
    }

    /**
     * Get the remaining hours of a ticket
     *
     * @return float
     */
    public function getLeftHours() {
        if ($this->isEstimated() === true) {
            return (float) (($this->hasWorkedHours() === true) ? $this->remaining_time : $this->getEstimation());
        }

        return 0;
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
     * Get the estimation
     *
     * @return float
     */
    public function getEstimation() {
        return (float) $this->estimated_time;
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
     * Check if a tickets has been worked on (only time)
     *
     * @return bool
     */
    public function hasWorkedHours() {
        return (bool) ($this->actual_time > 0);
    }

    /**
     * Get the actual worked hours
     *
     * @return float
     */
    public function getActualTime() {
        return (float) $this->actual_time;
    }

    /**
     * Check, if the bug is active & wip
     *
     * @return boolean
     */
    public function isWip() {
        return ($this->isContainer() === false and $this->getStatus() === Bug::STATUS_ASSIGNED);
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
     * Get the expected revenue
     *
     * @return float
     */
    public function getRevenue() {
        return (string) $this->_data->cf_expected_revenue;
    }

    /**
     * Get the probability of the expected revenue
     *
     * @return float
     */
    public function getRevenueProbability() {
        return (float) $this->_data->cf_expected_revenue_probability;
    }

    /**
     * Get the complexity of the project
     *
     * @return int
     */
    public function getComplexity() {
        return (int) $this->_data->cf_complexity;
    }

    /**
     * Get the risk-potential of the project
     *
     * @return int
     */
    public function getRisk() {
        return (int) $this->_data->cf_risk;
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
     * Get all dependencies as stack of tickets
     *
     * @return array
     */
    public function getDependsAsStack() {
        $aStack = array();
        foreach ($this->getDepends() as $iTicket) {
            $aStack[$iTicket] = $this->_oBugzilla->getBugById($iTicket);
        }

        return $aStack;
    }

    /**
     * Left hours of all dependencies
     *
     * @return float
     */
    public function getLeftTimeOfDependencies() {
        $fLeft = 0;
        $aDepends = $this->getDepends();
        foreach ($aDepends as $iTicket) {
            $fLeft += (float) $this->_oBugzilla->getBugById($iTicket)->getLeftHours();
        }

        return $fLeft;
    }

    /**
     * Estimated hours of all dependencies
     *
     * @return float
     */
    public function getEstimationTimeOfDependencies() {
        $fEstimation = 0;
        $aDepends = $this->getDepends();
        foreach ($aDepends as $iTicket) {
            $fEstimation += (float) $this->_oBugzilla->getBugById($iTicket)->getEstimation();
        }

        return $fEstimation;
    }

    /**
     * Actual hours of all dependencies
     *
     * @return float
     */
    public function getActualTimeOfDependencies() {
        $fActual = 0;
        $aDepends = $this->getDepends();
        foreach ($aDepends as $iTicket) {
            $fActual += (float) $this->_oBugzilla->getBugById($iTicket)->getActualTime();
        }

        return $fActual;
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
        if (is_null($this->_iPredecessor) !== true) {
            return $this->_iPredecessor;
        }

        $this->_iPredecessor = 0;
        if ($this->hasDependencies() === true) {
            $aEndDates = array();
            $dependencies = $this->getDepends();

            if (count($dependencies) > 1) {
                foreach ($dependencies as $dependency) {
                    try {
                        $oTicket = $this->_oBugzilla->getBugById($dependency);
                        if ($oTicket->isStatusAtMost(Bug::STATUS_REOPENED) === true and $oTicket->isContainer() === false) {
                            $aEndDates[$oTicket->id()] = $oTicket->getEndDate();
                        }
                    }
                    catch (Bug\Exception $e) {
                        $this->_oBugzilla->getLogger()->info($e->getTraceAsString());
                    }
                }

                if (empty($aEndDates) === true) {
                    $this->_iPredecessor = 0;
                    return $this->_iPredecessor;
                }

                arsort($aEndDates);
                $this->_iPredecessor = key($aEndDates);
            }
            else {
                $iDepends = (int) reset($dependencies);
                $oTicket = $this->_oBugzilla->getBugById($iDepends);
                if ($oTicket->isContainer() === false and $oTicket->isStatusAtMost(Bug::STATUS_REOPENED)) {
                    $this->_iPredecessor = $iDepends;
                }
            }
        }

        return $this->_iPredecessor;
    }

    /**
     * Determine, if the theme has unclosed dependencies
     *
     * @return boolean
     */
    public function hasUnclosedBugs() {
        $bReturn = false;
        if (isset($this->dependson) === true) {
            $aTickets = array();
            foreach ($this->dependson as $iBug) {
                $aTickets[(int) $iBug] = (int) $iBug;
            }

            $aTickets = $this->_oBugzilla->getBugListByIds($aTickets);
            foreach ($aTickets as $oTicket) {
                /* @var Bug $oTicket */
                try {
                    if ($oTicket->isContainer() === false) {
                        if ($oTicket->isClosed() !== true) {
                            $bReturn = true;
                        }

                        $this->_aDepends[$oTicket->id()] = $oTicket->id();
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
        return ($this->getStatus() === Bug::STATUS_CLOSED);
    }

    /**
     * Check, if a bug was merged
     *
     * @return boolean
     */
    public function isMerged() {
        return ($this->isClosed() === true or $this->isContainer() === true or ($this->hasFlag(Bug::FLAG_MERGE, Bugzilla::BUG_FLAG_GRANTED) === true and $this->hasFlag(Bug::FLAG_MERGE, Bugzilla::BUG_FLAG_REQUEST) === false));
    }

    /**
     * Return true, if this bug could have already been merged, due its dependencies
     *
     * @return boolean
     */
    public function couldBeInTrunk() {
        return ($this->isMerged() === true or $this->getDupe() !== false or ($this->hasFlag(Bug::FLAG_SCREEN, Bugzilla::BUG_FLAG_GRANTED) === true and $this->doesBlock() === true));
    }

    /**
     * A more in detail version of 'couldBeInTrunk'
     *
     * @return boolean
     */
    public function isMostLikelyInTrunk() {
        $bIsMostLikelyInTrunk = false;
        if ($this->couldBeInTrunk() === true) {
            $aBlocked                 = $this->_oBugzilla->getBugListByIds($this->blocks());
            $bTrunk                   = (empty($aBlocked) === true and $this->hasFlag(Bug::FLAG_SCREEN, Bugzilla::BUG_FLAG_GRANTED) === true) ? false : true;
            $bOnlyOrganizationTickets = (empty($aBlocked) === true) ? false : true;

            foreach ($aBlocked as $oBlocked) {
                /* @var $oBlocked Bug */
                if ($oBlocked->isContainer() !== true and $oBlocked->isConcept() !== true) {
                    $bOnlyOrganizationTickets = false;
                }

                if ($oBlocked->couldBeInTrunk() !== true and $oBlocked->isMerged() !== true) {
                    $bTrunk = false;
                }
            }

            $bIsMostLikelyInTrunk = ($bTrunk === true and $bOnlyOrganizationTickets === false);
        }

        return $bIsMostLikelyInTrunk;
    }

    /**
     * Check if a bug might be merged
     *
     * @return boolean
     */
    public function isMergeable() {
        $sStatus = $this->getStatus();
        $bTested = ($this->hasFlag(Bug::FLAG_TESTING, Bugzilla::BUG_FLAG_GRANTED) === true and $this->hasFlag(Bug::FLAG_TESTING, Bugzilla::BUG_FLAG_REQUEST) !== true);

        return (($this->hasFlag(Bug::FLAG_MERGE, Bugzilla::BUG_FLAG_REQUEST) === true or ($this->hasFlag(Bug::FLAG_MERGE, Bugzilla::BUG_FLAG_GRANTED) !== true and $bTested === true))
            and ($sStatus === Bug::STATUS_RESOLVED or $sStatus === Bug::STATUS_VERIFIED));
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
                    'setter' => (string) $flag['setter'],
                );

                if (isset($flag['requestee']) === true and $aFlag['status'] === Bugzilla::BUG_FLAG_REQUEST) {
                    $sUser = strtok($flag['requestee'], '@');
                    $aName = explode('.', strtoupper($sUser));
                    $this->_data->{strtolower($sName) . '_user'} = $aName[0]{0} . ((isset($aName[1]) === true) ? $aName[1]{0} : '');
                    $aFlag['requestee'] = $sUser;
                    $aFlag['requestee_mail'] = (string) $flag['requestee'];
                    $aFlag['requestee_short'] = (string) $this->_data->{strtolower($sName) . '_user'};
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
     * - optionally compare the a requestee or setter with $sUser
     *
     * @param  string $key
     * @param  string $value
     * @param  string $sUser
     * @param  string $sType
     *
     * @return boolean
     */
    public function hasFlag($key = null, $value = null, $sUser = null, $sType = self::FLAG_USER_REQUESTEE) {
        if (empty($this->_flags) === true) {
            return false;
        }

        $sHash = md5($key . $value . $sUser . $sType);
        if (isset($this->_aFlagCache[$sHash]) === true) {
            return $this->_aFlagCache[$sHash];
        }

        $return = false;
        foreach ($this->_flags as $aFlag) {
            if (isset($sUser) === true) {
                if (isset($aFlag[$sType]) === true and $aFlag[$sType] === $sUser and $aFlag['name'] === $key and $aFlag['status'] === $value) {
                    $return = true;
                }
            }
            else {
                if (isset($value) === true and isset($key) === false and $aFlag['status'] === $value) {
                    $return = true;
                }
                elseif (isset($value) === true and $aFlag['name'] === $key and $aFlag['status'] === $value) {
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
     * Get all requested flags
     *
     * @return array
     */
    public function getRequestedFlags() {
        $aFlags = array();
        foreach ($this->_flags as $aFlag) {
            if ($aFlag['status'] === Bugzilla::BUG_FLAG_REQUEST) {
                $aFlags[] = $aFlag;
            }
        }

        return $aFlags;
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
     * @param  boolean $bTok Apply strtok('@') before returning
     *
     * @return string
     */
    public function getReporter($bTok = false) {
        $sReporter = (string) $this->_data->reporter;
        return ($bTok === true) ? strtok($sReporter, '@') : $sReporter;
    }

    /**
     * Get the worked ours
     *
     * @return array
     */
    public function getWorkedHours() {
        if (empty($this->_aWorked) !== true) {
            return $this->_aWorked;
        }

        $aHistory = $this->_data->xpath('long_desc');
        $this->_aWorked = array();
        foreach ($aHistory as $oItem) {
            if (isset($oItem->work_time) === true) {
                $sResource = $this->_oResource->getResourceByEmail((string) $oItem->who);
                $iTime = strtotime((string) $oItem->bug_when);
                $this->_aWorked[] = array(
                    'date' => date('Y-m-d', $iTime),
                    'datetime' => $iTime,
                    'duration' => (float) $oItem->work_time,
                    'user' => $sResource,
                    'user_mail' => (string) $oItem->who,
                    'ticket' => $this->id()
                );
            }
        }

        unset($aHistory);

        return $this->_aWorked;
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
            $iTimestamp = $aTimes[0]['datetime'];
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
     * Get the time of creation of a ticket
     *
     * @return int
     */
    public function getCreationTime() {
        return strtotime($this->_data->creation_ts);
    }

    /**
     * Get the time since last modification
     *
     * @return float
     */
    public function getTimeSinceLastActivity() {
        $fTime = 0;
        $iLastActivity = $this->getLastActivity();
        if ($iLastActivity > 0) {
            $fTime = round(((time() - $iLastActivity) / 3600), 2);
        }

        return $fTime;
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
     * Get the assignee
     *
     * @param  boolean $bTok Apply strtok('@') before returning
     *
     * @return string
     */
    public function getAssignee($bTok = false) {
        $sAssignee = (string) $this->_data->assigned_to;
        if ($bTok === true) {
            $sAssignee = strtok($sAssignee, '@');
            $sAssignee = ucwords(str_replace(array('.', '-'), array(' ', '  '), $sAssignee));
            $sAssignee = str_replace('  ', '-', $sAssignee);
        }

        return $sAssignee;
    }
    /**
     *
     * Get the assignee (short)
     *
     * @return string
     */
    public function getAssigneeShort() {
        return $this->_data->assignee_short;
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
     * Get the planned week
     *
     * @return string | boolean
     */
    public function getWeek() {
        $sWeek = (string) $this->_data->cf_release_week;
        if (empty($sWeek) === true or $sWeek === '---') {
            $sWeek = false;
        }

        if ($sWeek === false and empty($this->cf_due_date) !== true) {
            $sWeek = date('Y/W', strtotime((string) $this->cf_due_date));
        }

        return $sWeek;
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
            throw new BugException(sprintf(BugException::INVALID_STATUS, $this->id(), $sComparisonStatus));
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
    public function isStatusAtMost($sComparisonStatus) {

        $sStatus = $this->getStatus();
        if (isset($this->_mappedStatus[$sComparisonStatus]) === false) {
            throw new BugException(sprintf(BugException::INVALID_STATUS, $this->id(), $sComparisonStatus));
        }
        elseif (empty($sStatus) === true) {
            throw new BugException(sprintf(BugException::INVALID_STATUS, $this->id(), ''));
        }

        return ($this->_mappedStatus[$sStatus] <= $this->_mappedStatus[$sComparisonStatus]);
    }
}
