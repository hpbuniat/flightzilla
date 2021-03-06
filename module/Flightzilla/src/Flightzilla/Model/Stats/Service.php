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
namespace Flightzilla\Model\Stats;

use Flightzilla\Model\Ticket\Type\Bug;
use Flightzilla\Model\Ticket\Source\Bugzilla;

/**
 * Service for statistics
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Service {

    /**
     * The stat-identifiers
     *
     * @var string
     */
    const STATS_FLAGS = 'flags';
    const STATS_WORKFLOW = 'workflow';
    const STATS_CHUCK = 'chuck';
    const STATS_PRIORITIES = 'priority';
    const STATS_SEVERITIES = 'severity';
    const STATS_STATUS = 'status';
    const STATS_THROUGHPUT = 'throughput';
    const STATS_TYPE = 'throughput';
    const STATS_DIFF = 'difference';
    const STATS_PROJECT = 'project-times';
    const STATS_ACTIVE_DAYS = 'days-active';
    const STATS_CREATE_DAYS = 'days-created';

    /**
     * The time window for the ticket-throughput
     *
     * @var string
     */
    const THROUGHPUT_WINDOW = 'last monday';

    /**
     * Identifier for diff-chart
     *
     * @var string
     */
    const DIFF_CREATED = 'created';
    const DIFF_RESOLVED = 'resolved';

    /**
     * The number of days for stats
     *
     * @var int
     */
    const TIME_WINDOW_4WEEKS = 28;
    const TIME_WINDOW_3WEEKS = 21;
    const TIME_WINDOW_2WEEKS = 14;
    const TIME_WINDOW_1WEEK = 7;

    /**
     * Mapping for ticket-types to progress-bar colors
     *
     * @var array
     */
    public static $aTypeColor = array(
        Bug::TYPE_CONCEPT => 'info',
        Bug::TYPE_BUG => 'warning',
        Bug::TYPE_FEATURE => 'success',
    );

    /**
     * The ticket-stack
     *
     * @var array
     */
    protected $_aStack = array();

    /**
     * The stack without closed tickets or container
     *
     * @var array
     */
    protected $_aFilteredStack = array();

    /**
     * The number of tickets
     *
     * @var int
     */
    protected $_iCount = 0;

    /**
     * Cache for stats
     *
     * @var array
     */
    protected $_aCache = array();

    /**
     * The configuration
     *
     * @var \Zend\Config\Config
     */
    protected $_config = null;

    /**
     * The filter-manager
     *
     * @var Filter\Manager
     */
    protected $_oFilterManager = null;

    /**
     * Create the stats-service
     *
     * @param  \Zend\Config\Config $oConfig
     * @param  Filter\Manager $oFilterManager
     */
    public function __construct(\Zend\Config\Config $oConfig, Filter\Manager $oFilterManager) {
        $this->_config = $oConfig;
        $this->_oFilterManager = $oFilterManager;
    }

    /**
     * Set the constraints for the filter-manager
     *
     * @param  array $aConstraints
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function setConstraints(array $aConstraints) {
        $this->_oFilterManager->resetConstraints();
        foreach ($aConstraints as $aConstraint) {
            if (isset($aConstraint['name']) === true and (isset($aConstraint['payload']) === true or is_null($aConstraint['payload']) === true)) {
                $this->addConstraint($aConstraint['name'], $aConstraint['payload']);
            }
            else {
                throw new \InvalidArgumentException('A constraint must contain name and payload!');
            }
        }

        return $this;
    }

    /**
     * Add a single constraint to the filter-manager
     *
     * @param  string $sName
     * @param  mixed $mPayload
     *
     * @return $this
     */
    public function addConstraint($sName, $mPayload) {
        if (is_null($mPayload) !== true) {
            $this->_oFilterManager->addConstraint($sName, $mPayload);
        }

        return $this;
    }

    /**
     * Apply the registered constraints
     *
     * @return $this
     */
    public function applyConstraints() {
        $this->_aFilteredStack = $this->_oFilterManager->check($this->_aStack);
        $this->_iCount = $this->_oFilterManager->getEntryCount();
        return $this;
    }

    /**
     * Set the stack of tickets
     *
     * @param  array $aStack
     *
     * @return $this
     */
    public function setStack(array $aStack) {
        $this->_aStack = $aStack;
        $this->applyConstraints();

        // flush the cache
        $this->_aCache = array();

        return $this;
    }

    /**
     * Is the stack empty?
     *
     * @return boolean
     */
    public function isStackEmpty() {
        return empty($this->_aStack);
    }

    /**
     * Get arbitrary stats by identifier
     *
     * @param  string $sStats
     *
     * @return array
     */
    public function get($sStats) {
        if (empty($this->_aCache[$sStats]) === true) {
            $this->_aCache[$sStats] = array();
        }

        return $this->_aCache[$sStats];
    }

    /**
     * Get the number of tickets
     *
     * @return int
     */
    public function getCount() {
        return $this->_iCount;
    }

    /**
     * Get time-stats for projects
     *
     * @param  int $iTimeWindow
     *
     * @return array
     */
    public function getProjectTimes($iTimeWindow = null) {

        $fTotalHours = 0;
        $aResult = array();
        foreach ($this->_aFilteredStack as $oTicket) {
            /* @var Bug $oTicket */
            $iTotalTime = $oTicket->getActualTime();
            if ($iTotalTime > 0) {
                $aProjects = $oTicket->getProjects();

                $aHoursPerUser = $this->_getWorkedHoursOfTimeWindow($oTicket->getWorkedHours(), $this->_getUnixTimeFromWindow($iTimeWindow));
                $iHoursWindow = $aHoursPerUser['total'];

                if (empty($aProjects) === true) {
                    if ($oTicket->isType(Bug::TYPE_BUG) === true) {
                        $aProjects[Bug::TYPE_BUG] = $oTicket;
                    }
                    elseif ($oTicket->hasContainer() === true) {
                        $iContainer = $oTicket->getContainer();
                        $aProjects[$iContainer] = $oTicket;
                    }
                    else {
                        $aProjects[Bug::TYPE_HOMELESS_FEATURE] = $oTicket;
                    }
                }

                if (empty($aProjects) !== true) {
                    $fTotalHours += $iHoursWindow;
                    foreach ($aProjects as $mProject => $oProject) {
                        /* @var Bug $oProject */
                        if (empty($aResult[$mProject]) === true) {
                            $aResult[$mProject] = array(
                                'hours' => 0,
                                'hoursWindow' => 0,
                                'hoursWorked' => 0,
                                'hoursLeft' => 0
                            );
                        }

                        $aResult[$mProject]['hours'] += $iTotalTime;
                        $aResult[$mProject]['hoursWindow'] += $iHoursWindow;
                        $aResult[$mProject]['hoursWorked'] += $iHoursWindow;
                        $aResult[$mProject]['hoursLeft'] += $oTicket->getLeftHours();
                        foreach ($aHoursPerUser['user'] as $sUser => $fHours) {
                            if (empty($aResult[$mProject]['users'][$sUser]) === true) {
                                $aResult[$mProject]['users'][$sUser] = array(
                                    'left' => 0,
                                    'worked' => 0
                                );
                            }

                            $aResult[$mProject]['users'][$sUser]['worked'] += $fHours;
                        }

                        $sUser = $oTicket->getAssignee(true);
                        if (empty($aResult[$mProject]['users'][$sUser]) === true) {
                            $aResult[$mProject]['users'][$sUser] = array(
                                'left' => 0,
                                'worked' => 0
                            );
                        }

                        $aResult[$mProject]['users'][$sUser]['left'] += $oTicket->getLeftHours();
                    }
                }

                unset ($aProjects, $aHoursPerUser);
            }
        }

        return array(
            'projects' => $this->_sortHelper($aResult, 'hoursWindow', true),
            'totalHours' => $fTotalHours,
            'future' => false
        );
    }

    /**
     * Get the planned hours per project & user
     *
     * @param  string $sWeek
     *
     * @return array
     */
    public function getFutureProjectTimes($sWeek) {
        $fTotalHours = 0;
        $aResult = array();

        foreach ($this->_aStack as $oTicket) {
            /* @var Bug $oTicket */
            $fActualHours = $this->_getWorkedHoursFromTicketOfWeek($oTicket, $sWeek);

            if ($oTicket->getWeek() === $sWeek or $fActualHours > 0) {
                $fTotalTime = $oTicket->getEstimation();
                $fLeftHours = $oTicket->getLeftHours();

                $aProjects = $oTicket->getProjects();
                $sUser = $oTicket->getAssignee(true);

                if (empty($aProjects) === true) {
                    if ($oTicket->isType(Bug::TYPE_BUG) === true) {
                        $aProjects[Bug::TYPE_BUG] = $oTicket;
                    }
                    elseif ($oTicket->hasContainer() === true) {
                        $iContainer = $oTicket->getContainer();
                        $aProjects[$iContainer] = $oTicket;
                    }
                    else {
                        $aProjects[Bug::TYPE_HOMELESS_FEATURE] = $oTicket;
                    }
                }

                if (empty($aProjects) !== true) {
                    $fTotalHours += $fLeftHours;
                    foreach ($aProjects as $mProject => $oProject) {
                        if (empty($aResult[$mProject]) === true) {
                            $aResult[$mProject] = array(
                                'hours' => 0,
                                'hoursWindow' => 0,
                                'hoursWorked' => 0,
                                'hoursLeft' => 0
                            );
                        }

                        $aResult[$mProject]['hours'] += $fTotalTime;
                        $aResult[$mProject]['hoursWindow'] += $fActualHours;
                        $aResult[$mProject]['hoursWorked'] += $fActualHours;
                        $aResult[$mProject]['hoursLeft'] += $fLeftHours;
                        if (empty($aResult[$mProject]['users'][$sUser]) === true) {
                            $aResult[$mProject]['users'][$sUser] = array(
                                'left' => 0,
                                'worked' => 0
                            );
                        }

                        $aResult[$mProject]['users'][$sUser]['left'] += $fLeftHours;
                        $aResult[$mProject]['users'][$sUser]['worked'] += $fActualHours;
                    }
                }

                unset ($aProjects, $sUser);
            }
        }

        return array(
            'projects' => $this->_sortHelper($aResult, 'hoursWindow', true),
            'totalHours' => $fTotalHours,
            'future' => true
        );
    }

    /**
     * Get times sorted by resource from project-times
     *
     * @param  array $aProjects
     *
     * @return array
     */
    public function getResourceTimesFromProjectTimes($aProjects) {
        $aResult = array();
        foreach ($aProjects['projects'] as $mProject => $aProject) {
            if (isset($aProject['users']) === true) {
                foreach ($aProject['users'] as $sUser => $aHours) {
                    if (empty($aResult[$sUser]) === true) {
                        $aResult[$sUser] = array(
                            'hours' => 0,
                            'hoursWindow' => 0,
                            'hoursWorked' => 0,
                            'hoursLeft' => 0
                        );
                    }

                    $aResult[$sUser]['hours'] += ($aHours['worked'] + $aHours['left']);
                    $aResult[$sUser]['hoursLeft'] += $aHours['left'];
                    $aResult[$sUser]['hoursWindow'] += $aHours['worked'];
                    $aResult[$sUser]['hoursWorked'] += $aHours['worked'];
                    if (empty($aResult[$sUser]['projects'][$mProject]) === true) {
                        $aResult[$sUser]['projects'][$mProject] = array(
                            'left' => 0,
                            'worked' => 0
                        );
                    }

                    $aResult[$sUser]['projects'][$mProject]['left'] += $aHours['left'];
                    $aResult[$sUser]['projects'][$mProject]['worked'] += $aHours['worked'];
                }
            }
        }

        return array(
            'resources' => $this->_sortHelper($aResult, 'hoursWindow', true),
            'totalHours' => $aProjects['totalHours'],
            'future' => $aProjects['future']
        );
    }

    /**
     * Sort a multi-dimensional array
     *
     * @param  array $aHaystack
     * @param  string $sKey
     * @param  boolean $bReverse
     *
     * @return array
     */
    protected function _sortHelper($aHaystack, $sKey, $bReverse = false) {
        $aSort = $aResult = array();
        foreach ($aHaystack as $mIndex => $aNeedle) {
            $aSort[$mIndex] = $aNeedle[$sKey];
        }

        ($bReverse === true) ? arsort($aSort) : asort($aSort);
        foreach ($aSort as $mIndex => $mNeedle) {
            $aResult[$mIndex] = $aHaystack[$mIndex];
        }

        unset($aSort);
        return $aResult;
    }

    /**
     * Get the worked hours in a particular time-frame
     *
     * @param  array $aHours
     * @param  int $iTimeWindow
     *
     * @return array
     */
    protected function _getWorkedHoursOfTimeWindow($aHours, $iTimeWindow) {
        $aWindowHours = array(
            'total' => 0,
            'user' => array()
        );
        foreach ($aHours as $aHour) {
            if (empty($iTimeWindow) === true or $aHour['datetime'] >= $iTimeWindow) {
                if (empty($aWindowHours['user'][$aHour['user']]) === true) {
                    $aWindowHours['user'][$aHour['user']] = 0;
                }

                $aWindowHours['total'] += $aHour['duration'];
                $aWindowHours['user'][$aHour['user']] += $aHour['duration'];
            }
        }

        return $aWindowHours;
    }

    /**
     * Determine if a ticket was worked on in a particular week
     *
     * @param  Bug $oTicket
     * @param  string $sWeek
     *
     * @return float
     */
    protected function _getWorkedHoursFromTicketOfWeek(Bug $oTicket, $sWeek) {
        $fWorkedHours = 0;
        $aHours = $oTicket->getWorkedHours();
        foreach ($aHours as $aHour) {
            $sWorkedWeek = date('Y/W', $aHour['datetime']);
            if ($sWorkedWeek === $sWeek) {
                $fWorkedHours += $aHour['duration'];
            }
        }

        unset($aHours);
        return $fWorkedHours;
    }

    /**
     * Get the unix-timestamp for a date X days in the past
     *
     * @param  int $iWindow
     *
     * @return int
     */
    protected function _getUnixTimeFromWindow($iWindow) {
        return strtotime(sprintf('-%d days', $iWindow), strtotime(date('Y-m-d')));
    }


    /**
     * Get the data for a daily-diff-chart
     *
     * @return array
     */
    public function getDailyDifference() {
        if (empty($this->_aCache[self::STATS_DIFF]) === true) {
            $aResult = array();
            $iRef = $this->_getUnixTimeFromWindow(self::TIME_WINDOW_4WEEKS);

            foreach ($this->_aFilteredStack as $oTicket) {
                /* @var Bug $oTicket */
                if (($oTicket->getLastWorkDate() >= $iRef or $oTicket->getCreationTime() >= $iRef)) {
                    if ($oTicket->getCreationTime() >= $iRef) {
                        $sCreationDate = date('Ymd', $oTicket->getCreationTime());
                        if (empty($aResult[$sCreationDate]) === true) {
                            $aResult[$sCreationDate] = array(
                                self::DIFF_CREATED => 0,
                                self::DIFF_RESOLVED => 0,
                            );
                        }

                        $aResult[$sCreationDate][self::DIFF_CREATED]++;
                    }

                    if ($oTicket->isStatusAtLeast(Bug::STATUS_RESOLVED) === true and $oTicket->hasWorkedHours() === true) {
                        $sResolveDate = date('Ymd', $oTicket->getLastWorkDate());
                        if (empty($aResult[$sResolveDate]) === true) {
                            $aResult[$sResolveDate] = array(
                                self::DIFF_CREATED => 0,
                                self::DIFF_RESOLVED => 0,
                            );
                        }

                        $aResult[$sResolveDate][self::DIFF_RESOLVED]++;
                    }
                }
            }

            // transform this to a structure which gives a nice json
            ksort($aResult);
            $this->_aCache[self::STATS_DIFF] = array(
                'daily' => array(),
                'total' => array(
                    self::DIFF_CREATED => 0,
                    self::DIFF_RESOLVED => 0
                )
            );
            foreach ($aResult as $sDate => $aValue) {
                $aValue['date'] = $sDate;
                $this->_aCache[self::STATS_DIFF]['daily'][] = $aValue;
                if (isset($aValue[self::DIFF_CREATED]) === true) {
                    $this->_aCache[self::STATS_DIFF]['total'][self::DIFF_CREATED] += $aValue[self::DIFF_CREATED];
                }

                if (isset($aValue[self::DIFF_RESOLVED]) === true) {
                    $this->_aCache[self::STATS_DIFF]['total'][self::DIFF_RESOLVED] += $aValue[self::DIFF_RESOLVED];
                }
            }

            unset($aResult);
        }

        return $this->_aCache[self::STATS_DIFF];
    }

    /**
     * Get the rate of features and bugs
     *
     * @return array
     */
    public function getFeatureBugRate() {
        $aResult = array();
        foreach ($this->_aFilteredStack as $oTicket) {
            /* @var Bug $oTicket */
            $sType = $oTicket->getType();
            if (empty($aResult[$sType]) === true) {
                $aResult[$sType] = 0;
            }

            $aResult[$sType]++;
        }

        ksort($aResult);
        $this->_percentify($aResult, $this->getCount());
        return $aResult;
    }

    /**
     * Get the efficiency for tickets (which tickets are in production)
     *
     * @return array
     */
    public function getTicketEfficiency() {
        $aResult = array(
            'invested' => 0,
            'returned' => 0
        );
        foreach ($this->_aFilteredStack as $oTicket) {
            /* @var Bug $oTicket */
            if ($oTicket->isClosed() === true) {
                $aResult['returned'] += $oTicket->getActualTime();
            }
            else {
                $aResult['invested'] += $oTicket->getActualTime();
            }
        }

        $this->_percentify($aResult, ($aResult['invested'] + $aResult['returned']));
        return $aResult;
    }

    /**
     * Get the bug-stats
     *
     * @return array
     */
    public function getWorkflowStats() {

        if (empty($this->_aCache[self::STATS_WORKFLOW]) === true) {
            $this->_aCache[self::STATS_WORKFLOW] = array(
                Bug::WORKFLOW_ESTIMATED   => 0,
                Bug::WORKFLOW_ORGA        => 0,
                Bug::WORKFLOW_UNESTIMATED => 0,
                Bug::WORKFLOW_INPROGRESS  => 0,
                Bug::WORKFLOW_ACTIVE      => 0,
                Bug::WORKFLOW_TESTING     => 0,
                Bug::WORKFLOW_MERGE       => 0,
                Bug::WORKFLOW_DEADLINE    => 0,
                Bug::WORKFLOW_SCREEN      => 0,
                Bug::WORKFLOW_COMMENT     => 0,
                Bug::WORKFLOW_FAILED      => 0,
                Bug::WORKFLOW_QUICK       => 0,
                Bug::WORKFLOW_TRANSLATION => 0,
                Bug::WORKFLOW_TRANSLATION_PENDING => 0,
                Bug::WORKFLOW_TIMEDOUT    => 0,
                Bug::WORKFLOW_DB_CHANGE    => 0,
                Bug::WORKFLOW_NO_CONTAINER    => 0,
                Bug::WORKFLOW_PRODUCT_DEPENDENCY    => 0,
            );

            $iTimeoutLimit = $this->_config->tickets->workflow->timeout;
            foreach ($this->_aFilteredStack as $oTicket) {
                /* @var $oTicket Bug */

                $bShouldHaveEstimation = true;
                if ($oTicket->isOrga() === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_ORGA]++;
                    $bShouldHaveEstimation = false;
                }

                if ($oTicket->isEstimated() === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_ESTIMATED]++;
                }
                elseif ($bShouldHaveEstimation === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_UNESTIMATED]++;
                }

                if ($oTicket->isWorkedOn() === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_INPROGRESS]++;
                }

                if ($oTicket->isActive() === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_ACTIVE]++;
                }

                if ($oTicket->hasFlag(Bug::FLAG_TESTING, Bugzilla::BUG_FLAG_REQUEST) === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_TESTING]++;
                }

                if ($oTicket->isFailed() === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_FAILED]++;
                }

                if ($oTicket->isMergeable() === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_MERGE]++;
                }

                if ($oTicket->deadlineStatus() === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_DEADLINE]++;
                }

                if ($oTicket->hasFlag(Bug::FLAG_SCREEN, Bugzilla::BUG_FLAG_REQUEST)) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_SCREEN]++;
                }

                if ($oTicket->hasFlag(Bug::FLAG_COMMENT, Bugzilla::BUG_FLAG_REQUEST) or $oTicket->getStatus() === Bug::STATUS_CLARIFICATION) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_COMMENT]++;
                }

                if ($oTicket->isQuickOne() === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_QUICK]++;
                }

                if ($oTicket->isOnlyTranslation() === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_TRANSLATION]++;
                }

                if ($oTicket->hasDependenciesToOtherProducts() === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_PRODUCT_DEPENDENCY]++;
                }

                if ($oTicket->hasMissingContainer() === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_NO_CONTAINER]++;
                }

                if ($oTicket->hasFlag(Bug::FLAG_DBCHANGE, Bugzilla::BUG_FLAG_REQUEST) === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_DB_CHANGE]++;
                }

                if ($oTicket->hasFlag(Bug::FLAG_TRANSLATION, Bugzilla::BUG_FLAG_REQUEST) === true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_TRANSLATION_PENDING]++;
                }

                if ($oTicket->isChangedWithinLimit($iTimeoutLimit) !== true) {
                    $this->_aCache[self::STATS_WORKFLOW][Bug::WORKFLOW_TIMEDOUT]++;
                }
            }

            $this->_percentify($this->_aCache[self::STATS_WORKFLOW], $this->getCount());
        }

        return $this->_aCache[self::STATS_WORKFLOW];
    }

    /**
     * Get the chuck-status
     *
     * @return string
     */
    public function getChuckStatus() {

        if (empty($this->_aCache[self::STATS_CHUCK]) === true) {

            $this->_aCache[self::STATS_CHUCK] = \Flightzilla\Model\Chuck::OK;
            if (empty($this->_aCache[self::STATS_WORKFLOW]) === true or empty($this->_aCache[self::STATS_STATUS]) === true) {
                $this->getWorkflowStats();
                $this->getStatuses();
            }

            $aWorkflow = $this->_aCache[self::STATS_WORKFLOW];
            $aStatus = $this->_aCache[self::STATS_STATUS];
            if (empty($aWorkflow[Bug::WORKFLOW_UNESTIMATED]) !== true and $aWorkflow[Bug::WORKFLOW_UNESTIMATED]['per'] > 10) {
                $this->_aCache[self::STATS_CHUCK] = \Flightzilla\Model\Chuck::WARN;
            }
            elseif (empty($aWorkflow[Bug::STATUS_UNCONFIRMED]) !== true and $aStatus[Bug::STATUS_UNCONFIRMED]['per'] > 10) {
                $this->_aCache[self::STATS_CHUCK] = \Flightzilla\Model\Chuck::WARN;
            }

            if (empty($aStatus[Bug::STATUS_REOPENED]) !== true and $aStatus[Bug::STATUS_REOPENED]['num'] > 1) {
                $this->_aCache[self::STATS_CHUCK] = \Flightzilla\Model\Chuck::ERROR;
            }
            elseif (empty($aWorkflow[Bug::WORKFLOW_FAILED]) !== true and $aWorkflow[Bug::WORKFLOW_FAILED]['per'] > 2) {
                $this->_aCache[self::STATS_CHUCK] = \Flightzilla\Model\Chuck::ERROR;
            }
            elseif (empty($aWorkflow[Bug::WORKFLOW_UNESTIMATED]) !== true and $aWorkflow[Bug::WORKFLOW_UNESTIMATED]['per'] > 15) {
                $this->_aCache[self::STATS_CHUCK] = \Flightzilla\Model\Chuck::WARN;
            }
            elseif (empty($aWorkflow[Bug::STATUS_UNCONFIRMED]) !== true and $aStatus[Bug::STATUS_UNCONFIRMED]['per'] > 15) {
                $this->_aCache[self::STATS_CHUCK] = \Flightzilla\Model\Chuck::WARN;
            }

            unset($aStatus, $aWorkflow);
        }

        return $this->_aCache[self::STATS_CHUCK];
    }

    /**
     * Get all open flags sorted by requestee
     *
     * @return array
     */
    public function getFlagStats() {
        if (empty($this->_aCache[self::STATS_FLAGS]) === true) {
            $aResult = array();

            foreach ($this->_aFilteredStack as $oTicket) {
                /* @var $oTicket Bug */
                $aFlags = $oTicket->getRequestedFlags();
                foreach ($aFlags as $aFlag) {
                    $sName = $aFlag['name'];
                    $sReq = (isset($aFlag['requestee']) === true) ? $aFlag['requestee'] : 'anonymous';
                    if (empty($aResult[$sReq]) === true) {
                        $aResult[$sReq] = array();
                    }

                    if (empty($aResult[$sReq][$sName]) === true) {
                        $aResult[$sReq][$sName] = array();
                    }

                    $aResult[$sReq][$sName][] = array(
                        'flag' => $aFlag,
                        'ticket' => $oTicket
                    );
                }
            }

            ksort($aResult);
            foreach ($aResult as $sReq => $aFlags) {
                ksort($aResult[$sReq]);
            }

            $this->_aCache[self::STATS_FLAGS] = $aResult;
        }

        return $this->_aCache[self::STATS_FLAGS];
    }

    /**
     * Return all statuses with percentage.
     *
     * @return array
     */
    public function getStatuses() {

        return $this->_getGenericMethodAttribute(self::STATS_STATUS, 'getStatus');
    }

    /**
     * Get the priorities
     *
     * @return array
     */
    public function getPriorities() {

        return $this->_getGenericMethodAttribute(self::STATS_PRIORITIES, 'getPriority');
    }

    /**
     * Get the priorities
     *
     * @return array
     */
    public function getSeverities() {

        return $this->_getGenericMethodAttribute(self::STATS_SEVERITIES, 'getSeverity');
    }

    /**
     * Get the tickets per activity within 0 to 7 days
     *
     * @return array
     */
    public function getTicketsActiveWithinDays() {

        return $this->_getGenericDateAttribute(self::STATS_ACTIVE_DAYS, 'getLastActivity');
    }

    /**
     * Get the tickets per activity within 0 to 7 days
     *
     * @return array
     */
    public function getTicketsCreatedWithinDays() {

        return $this->_getGenericDateAttribute(self::STATS_CREATE_DAYS, 'getCreationTime');
    }

    /**
     * Generic getter for date-attribute comparisson
     *
     * @param  string $sCacheEntry
     * @param  string $sMethod
     * @param  int $iDayStart
     * @param  int $iDayEnd
     *
     * @return array
     */
    protected function _getGenericDateAttribute($sCacheEntry, $sMethod, $iDayStart = 0, $iDayEnd = self::TIME_WINDOW_1WEEK) {
        if (empty($this->_aCache[$sCacheEntry]) === true) {
            $this->_aCache[$sCacheEntry] = array();

            $iDaysActive = $iDayStart;
            while ($iDaysActive < $iDayEnd) {
                $sCompareDate = date('dmy', strtotime(sprintf('-%d days', $iDaysActive)));
                foreach ($this->_aFilteredStack as $oTicket) {
                    /* @var $oTicket Bug */
                    if (date('dmy', $oTicket->$sMethod()) === $sCompareDate) {
                        if (empty($this->_aCache[$sCacheEntry][$iDaysActive]) === true) {
                            $this->_aCache[$sCacheEntry][$iDaysActive] = 0;
                        }

                        $this->_aCache[$sCacheEntry][$iDaysActive]++;
                    }
                }

                $iDaysActive++;
            }

            $this->_percentify($this->_aCache[$sCacheEntry], $this->getCount());
            ksort($this->_aCache[$sCacheEntry]);
        }

        return $this->_aCache[$sCacheEntry];
    }

    /**
     * Generic getter for comparisson based on method-results
     *
     * @param  string $sCacheEntry
     * @param  string $sMethod
     *
     * @return array
     */
    protected function _getGenericMethodAttribute($sCacheEntry, $sMethod) {
        if (empty($this->_aCache[$sCacheEntry]) === true) {
            $this->_aCache[$sCacheEntry] = array();

            foreach ($this->_aFilteredStack as $oTicket) {
                /* @var $oTicket Bug */
                $sProperty = $oTicket->$sMethod();
                if (empty($this->_aCache[$sCacheEntry][$sProperty]) === true) {
                    $this->_aCache[$sCacheEntry][$sProperty] = 0;
                }

                $this->_aCache[$sCacheEntry][$sProperty]++;
            }

            $this->_percentify($this->_aCache[$sCacheEntry], $this->getCount());
            ksort($this->_aCache[$sCacheEntry]);
        }

        return $this->_aCache[$sCacheEntry];
    }

    /**
     * Get the ticket-throughput-diff for this week
     *
     * @return int
     */
    public function getThroughPut() {

        if (empty($this->_aCache[self::STATS_THROUGHPUT]) === true) {
            $this->_aCache[self::STATS_THROUGHPUT] = 0;
            $iCompare = strtotime(self::THROUGHPUT_WINDOW);
            foreach ($this->_aStack as $oTicket) {
                /* @var $oTicket Bug */
                if ($oTicket->isAdministrative() !== true) {
                    if ($oTicket->getCreationTime() > $iCompare) {
                        $this->_aCache[self::STATS_THROUGHPUT]++;
                    }

                    if ($oTicket->isStatusAtLeast(Bug::STATUS_RESOLVED) === true and $oTicket->getLastActivity() > $iCompare) {
                        $this->_aCache[self::STATS_THROUGHPUT]--;
                    }
                }
            }
        }

        return $this->_aCache[self::STATS_THROUGHPUT];
    }

    /**
     * Get the number of days which are used to determine the ticket-throughput
     *
     * @return int
     */
    public function getThroughPutDays() {
        return ceil((time() - strtotime(self::THROUGHPUT_WINDOW)) / 86400);
    }

    /**
     * Get the percentage of each type in the stack
     *
     * @param  array $aStack
     * @param  int   $iCount
     *
     * @return Bugzilla
     */
    protected function _percentify(array &$aStack, $iCount) {

        $mStat = null;
        foreach ($aStack as &$mStat) {
            $mStat = array(
                'num' => $mStat,
                'per' => ($iCount === 0) ? 0 : round(($mStat / $iCount) * 100, 2),
                'sum' => $iCount
            );
        }

        unset($mStat);
        return $this;
    }
}
