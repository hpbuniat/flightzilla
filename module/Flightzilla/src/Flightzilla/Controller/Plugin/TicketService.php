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
namespace Flightzilla\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin,
    Zend\Mvc\Controller\AbstractActionController;

/**
 * A plugin to init the ticket-service
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class TicketService extends AbstractTicketService {

    /**
     * Name of the plugin
     *
     * @var string
     */
    const NAME = 'ticketservice';

    /**
     * Init the ticket-service
     *
     * @param  \Zend\View\Model\ViewModel $oViewModel
     * @param  string $sMode
     * @param  int $iRefreshDays
     *
     * @return $this
     */
    public function init(\Zend\View\Model\ViewModel $oViewModel, $sMode = 'list', $iRefreshDays = 0) {
        $oTicketService = $this->getService();
        /* @var $oTicketService \Flightzilla\Model\Ticket\Source\Bugzilla */

        $oTicketService->getBugList()
                       ->getChangedTicketsWithinDays($oTicketService->getSearchTimeModifier($oTicketService->getLastRequestTime($iRefreshDays), $iRefreshDays));

        if ($iRefreshDays > 0) {
            $oTicketService->getChangedTicketsWithinDays($oTicketService->getSearchTimeModifier($oTicketService->getLastRequestTime(0), 0));
        }

        $oTicketService->warmUp();

        // set the sprint-weeks
        $oViewModel->aWeeks = $oTicketService->getDate()->getWeeks(1);

        // gather the ticket-information
        if ($sMode !== 'history') {
            $oViewModel->bugsReopened = $oTicketService->getReopenedBugs();
            $oViewModel->bugsTestserver = $oTicketService->getUpdateTestserver();
            $oViewModel->bugsBranch = $oTicketService->getFixedBugsInBranch();
            $oViewModel->bugsTrunk = $oTicketService->getFixedBugsInTrunk();
            $oViewModel->bugsFixed = $oTicketService->getFixedBugsUnknown();
            $oViewModel->bugsOpen = $oTicketService->getThemedTickets();
            $oViewModel->bugsUnthemed = $oTicketService->getUnthemedBugs();
            $oViewModel->mergedOpen = $oTicketService->getMergedButOpen();
        }

        if ($sMode === 'board') {

            $oKanbanStatus = new \Flightzilla\Model\Kanban\Status($oTicketService->getAllBugs(), $oTicketService);
            $oViewModel->aKanban = $oKanbanStatus->setTypes(array(
                \Flightzilla\Model\Ticket\Type\Bug::TYPE_BUG,
                \Flightzilla\Model\Ticket\Type\Bug::TYPE_FEATURE,
                \Flightzilla\Model\Ticket\Type\Bug::TYPE_CONCEPT,
            ))->process()->get();
        }
        elseif ($sMode !== 'history') {
            $oViewModel->aUntouched = $oTicketService->getUntouched();
        }

        if ($sMode !== 'status' and $sMode !== 'history') {
            $oViewModel->aMemberBugs = $oTicketService->getMemberBugs();
            $oViewModel->aTeamBugs = $oTicketService->getTeamBugs($oViewModel->aMemberBugs);
            $oViewModel->aDeadlineStack = $oTicketService->getDeadlineStack();
            $oViewModel->aThemes = $oTicketService->getThemesAsStack();

            // expose some those objects to the view
            $oViewModel->oTicketService = $oTicketService;
            $oViewModel->oResourceManager = $oTicketService->getResourceManager();

            if ($sMode === 'sprint') {
                $oViewModel->aWeekTickets = $oTicketService->getWeekSprint($oViewModel->aTeamBugs);
            }
        }

        $oTicketStats = $this->_oService->getStats();
        $oTicketStats->setConstraints(array(
            array(
                'name' => \Flightzilla\Model\Stats\Filter\Constraint\GenericMethodInverse::NAME,
                'payload' => 'isClosed',
            ),
            array(
                'name' => \Flightzilla\Model\Stats\Filter\Constraint\GenericMethodInverse::NAME,
                'payload' => 'isContainer',
            )
        ));
        if ($oTicketStats->isStackEmpty() === true) {
            $oTicketStats->setStack($oTicketService->getAllBugs());
        }

        $oViewModel->iTotal = $oTicketService->getCount();
        $oViewModel->aStats = $oTicketStats->getWorkflowStats();
        $oViewModel->aStatuses = $oTicketStats->getStatuses();
        $oViewModel->aPriorities = $oTicketStats->getPriorities();
        $oViewModel->aSeverities = $oTicketStats->getSeverities();
        $oViewModel->aDaysCreated = $oTicketStats->getTicketsCreatedWithinDays();
        $oViewModel->aDaysActive = $oTicketStats->getTicketsActiveWithinDays();
        $oViewModel->sChuck = $oTicketStats->getChuckStatus();

        $oViewModel->iThroughPut = $oTicketStats->getThroughPut();

        $oTasks = new \Flightzilla\Model\Ticket\Task\Manager($oTicketService);
        $oViewModel->aTasks = $oTasks->check($oTicketService->getAllBugs());
        $oViewModel->iEntries = $oTasks->getEntryCount();

        return $this;
    }
}
