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
    Flightzilla\Controller\Plugin\Authenticate,
    Flightzilla\Controller\Plugin\TicketService;

/**
 * Team-related controller
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class TeamController extends AbstractActionController {

    /**
     *
     */
    public function indexAction() {
        $oViewModel = new ViewModel;
        $oViewModel->mode = 'team';

        $this->getPluginManager()->get(TicketService::NAME)->init($oViewModel);
        return $oViewModel;
    }

    /**
     * Show the team-dashboard
     */
    public function dashboardAction() {
        $oViewModel = new ViewModel;
        $oViewModel->mode = 'team';

        $this->getPluginManager()->get(TicketService::NAME)->init($oViewModel);
        return $oViewModel;
    }

    /**
     * Show all team-members
     */
    public function membersAction() {
        $oViewModel = new ViewModel;
        $oViewModel->setTerminal(true);

        $oTicketService = $this->getPluginManager()->get(TicketService::NAME)->getService();
        $oViewModel->oResourceManager = $oTicketService->getResourceManager();
        $oViewModel->aTeam = $oTicketService->getTeam();

        return $oViewModel;
    }

    /**
     *
     */
    public function summaryAction() {
        $oViewModel = new ViewModel;
        $oViewModel->mode = 'summary';

        $sDate = $this->params()->fromPost('date');
        if (strtotime($sDate) === false or strtotime($sDate) === 0) {
            $sDate = '';
        }

        if (empty($sDate) === true) {
            $sDate = date('Y-m-d', strtotime('last weekday'));
        }

        $oViewModel->sDate = $sDate;
        $oViewModel->bugsSummary = $this->getPluginManager()->get(TicketService::NAME)->getService()->getSummary($sDate);
        return $oViewModel;
    }

    /**
     *
     */
    public function reviewAction() {
        $oViewModel = new ViewModel;
        $oViewModel->mode = 'team';

        $iDays = 7;
        $oTicketService = $this->getPluginManager()->get(TicketService::NAME)->init($oViewModel, 'list', $iDays)->getService();
        $oViewModel->sResource = json_encode($oTicketService->getResourceManager()->getActivities($iDays));

        return $oViewModel;
    }
}
