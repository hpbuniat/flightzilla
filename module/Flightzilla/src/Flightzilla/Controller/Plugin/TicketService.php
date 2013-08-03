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
class TicketService extends AbstractPlugin {

    /**
     * Name of the plugin
     *
     * @var string
     */
    const NAME = 'ticketservice';

    /**
     * The ticket-service
     *
     * @var \Flightzilla\Model\Ticket\AbstractSource
     */
    protected $_oService = null;

    /**
     * Get the ticket-service
     *
     * @return \Flightzilla\Model\Ticket\AbstractSource
     */
    public function getService() {
        if (empty($this->_oService) === true) {
            $this->_oService = $this->getController()->getServiceLocator()->get('_bugzilla');
        }

        return $this->_oService;
    }

    /**
     * Set the ticket-service
     *
     * @param  \Flightzilla\Model\Ticket\AbstractSource $oService
     *
     * @return $this
     */
    public function setService(\Flightzilla\Model\Ticket\AbstractSource $oService) {
        $this->_oService = $oService;
        return $this;
    }

    /**
     * Init the ticket-service
     *
     * @param  \Zend\View\Model\ViewModel $oView
     * @param  string $sMode
     * @param  int $iRefreshDays
     *
     * @return $this
     */
    public function init(\Zend\View\Model\ViewModel $oView, $sMode = 'list', $iRefreshDays = 0) {
        $oTicketService = $this->getService();
        /* @var $oTicketService \Flightzilla\Model\Ticket\Source\Bugzilla */

        $oTicketService->getBugList()
                       ->getChangedTicketsWithinDays($oTicketService->getSearchTimeModifier($oTicketService->getLastRequestTime($iRefreshDays), $iRefreshDays))
                       ->warmUp();

        // set the sprint-weeks
        $oView->aWeeks = $oTicketService->getDate()->getWeeks(1);

        // gather the ticket-information
        if ($sMode !== 'history') {
            $oView->bugsReopened = $oTicketService->getReopenedBugs();
            $oView->bugsTestserver = $oTicketService->getUpdateTestserver();
            $oView->bugsBranch = $oTicketService->getFixedBugsInBranch();
            $oView->bugsTrunk = $oTicketService->getFixedBugsInTrunk();
            $oView->bugsFixed = $oTicketService->getFixedBugsUnknown();
            $oView->bugsOpen = $oTicketService->getThemedTickets();
            $oView->bugsUnthemed = $oTicketService->getUnthemedBugs();
        }

        if ($sMode === 'board') {

            $oKanbanStatus = new \Flightzilla\Model\Kanban\Status($oTicketService->getAllBugs(), $oTicketService);
            $oView->aKanban = $oKanbanStatus->setTypes(array(
                \Flightzilla\Model\Ticket\Type\Bug::TYPE_BUG,
                \Flightzilla\Model\Ticket\Type\Bug::TYPE_FEATURE,
                \Flightzilla\Model\Ticket\Type\Bug::TYPE_CONCEPT,
            ))->process()->get();
        }
        elseif ($sMode !== 'history') {
            $oView->aUntouched = $oTicketService->getUntouched();
        }

        if ($sMode !== 'status' and $sMode !== 'history') {
            $oView->aMemberBugs = $oTicketService->getMemberBugs();
            $oView->aTeamBugs = $oTicketService->getTeamBugs($oView->aMemberBugs);
            $oView->aDeadlineStack = $oTicketService->getDeadlineStack();
            $oView->aThemes = $oTicketService->getThemesAsStack();

            // expose some those objects to the view
            $oView->oTicketService = $oTicketService;
            $oView->oResourceManager = $oTicketService->getResourceManager();

            if ($sMode === 'sprint') {
                $oView->aWeekTickets = $oTicketService->getWeekSprint($oView->aTeamBugs);
            }
        }

        $oTicketStats = $this->_oService->getStats();
        if ($oTicketStats->isStackEmpty() === true) {
            $oTicketStats->setStack($oTicketService->getAllBugs());
        }

        $oView->iTotal = $oTicketService->getCount();
        $oView->aStats = $oTicketStats->getWorkflowStats();
        $oView->aStatuses = $oTicketStats->getStatuses();
        $oView->aPriorities = $oTicketStats->getPriorities();
        $oView->aSeverities = $oTicketStats->getSeverities();
        $oView->sChuck = $oTicketStats->getChuckStatus();
        $oView->iThroughPut = $oTicketStats->getThroughPut();

        $oTasks = new \Flightzilla\Model\Ticket\Task\Manager($oTicketService);
        $oView->aTasks = $oTasks->check($oTicketService->getAllBugs());
        $oView->iEntries = $oTasks->getEntryCount();

        return $this;
    }
}
