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
namespace Flightzilla\Controller;

use Zend\Mvc\Controller\AbstractActionController,
    Zend\View\Model\ViewModel,
    Flightzilla\Controller\Plugin\TicketService,
    Flightzilla\Model\Stats\Service as StatsService;

/**
 * Access statistics
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class StatsController extends AbstractActionController {

    /**
     *
     */
    public function indexAction() {

        $oViewModel = new ViewModel;
        $oViewModel->mode = 'dashboard';

        $oTicketPlugin = $this->getPluginManager()->get(TicketService::NAME);
        $oTicketService = $oTicketPlugin->getService();
        $oTicketPlugin->init($oViewModel, $oViewModel->mode, StatsService::TIME_WINDOW_4WEEKS);

        /* @var $oTicketStats StatsService */
        $oTicketStats = $oTicketService->getStats();
        $oViewModel->oTicketService = $oTicketService;

        // use the "current" stack to get the feature/bug-rate for the current-ticket-list
        $aAdministrativeConstraint = array(
            'name' => \Flightzilla\Model\Stats\Filter\Constraint\GenericMethodInverse::NAME,
            'payload' => 'isAdministrative',
        );

        $aContainerConstraint = array(
            'name' => \Flightzilla\Model\Stats\Filter\Constraint\GenericMethodInverse::NAME,
            'payload' => 'isContainer',
        );

        $oTicketStats->setConstraints(array(
            $aContainerConstraint,
            $aAdministrativeConstraint
        ))->setStack($oTicketService->getAllBugs());
        $aStatsFeatureTickets = array(
            'current' => $oTicketStats->getFeatureBugRate()
        );

        // set the 4 week-constraints to get the daily-diff for 4 weeks
        $aConstraints = array(
            $aContainerConstraint,
            $aAdministrativeConstraint,
            \Flightzilla\Model\Stats\Filter\Constraint\Activity::NAME => array(
                'name' => \Flightzilla\Model\Stats\Filter\Constraint\Activity::NAME,
                'payload' => StatsService::TIME_WINDOW_4WEEKS,
            )
        );

        $oTicketStats->setConstraints($aConstraints)->setStack($oTicketService->getAllBugs());
        $oViewModel->aDailyDifference = $oTicketStats->getDailyDifference();

        $aIterateFeatureTickets = array(
            'last week' => StatsService::TIME_WINDOW_1WEEK,
            '2 weeks' => StatsService::TIME_WINDOW_2WEEKS,
            '3 weeks' => StatsService::TIME_WINDOW_3WEEKS,
            '4 weeks' => StatsService::TIME_WINDOW_4WEEKS
        );

        $aResourceTimes = $aTicketEfficiency = $aProjectTimes = array();
        foreach ($aIterateFeatureTickets as $sTime => $iFilter) {
            $aConstraints[\Flightzilla\Model\Stats\Filter\Constraint\Activity::NAME]['payload'] = $iFilter;
            $oTicketStats->setConstraints($aConstraints)->applyConstraints();
            $aTicketEfficiency[$sTime] = $oTicketStats->getTicketEfficiency();
            $aStatsFeatureTickets[$sTime] = $oTicketStats->getFeatureBugRate();
            $aProjectTimes[$sTime] = $oTicketStats->getProjectTimes($iFilter);
            $aResourceTimes[$sTime] = $oTicketStats->getResourceTimesFromProjectTimes($aProjectTimes[$sTime]);
        }

        // reset the stats to get current values
        $aConstraints = array(
            $aContainerConstraint,
            $aAdministrativeConstraint
        );

        $oTicketStats->setConstraints($aConstraints)->setStack($oTicketService->getAllBugs());

        $aProjectTimes = array_reverse($aProjectTimes, true);
        $aResourceTimes = array_reverse($aResourceTimes, true);
        foreach ($oViewModel->aWeeks as $sWeek => $aWeek) {
            $sTitle = sprintf('%s (%s)', $sWeek, $aWeek['title']);
            $aProjectTimes[$sTitle] = $oTicketStats->getFutureProjectTimes($aWeek['title']);
            $aResourceTimes[$sTitle] = $oTicketStats->getResourceTimesFromProjectTimes($aProjectTimes[$sTitle]);
        }

        $oViewModel->aResourceTimes = $aResourceTimes;
        $oViewModel->aTicketEfficiency = $aTicketEfficiency;
        $oViewModel->aStatsFeatureTickets = $aStatsFeatureTickets;
        $oViewModel->aProjectTimes = $aProjectTimes;
        return $oViewModel;
    }
}

