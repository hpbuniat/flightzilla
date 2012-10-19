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

namespace Flightzilla\Controller;

use Zend\Mvc\Controller\AbstractActionController,
    Zend\View\Model\ViewModel,
    Flightzilla\Controller\Plugin\Authenticate,
    Flightzilla\Controller\Plugin\TicketService;

/**
 * Ticket-related controller
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class IndexController extends AbstractActionController {

    /**
     * Login
     */
    public function loginAction() {
        $oAuth = $this->getPluginManager()->get(Authenticate::NAME);
        /* @var $oAuth Authenticate */
        $oAuth->clearIdentity();

        $oParams = $this->params();
        if ($oParams->fromPost('username', false) !== false and $oParams->fromPost('password', false) !== false) {
            if ($oAuth->performAuthentication()->hasIdentity() === true) {
                $oAuth->persist();
            }

            $this->redirect()->toRoute('home');
        }
    }

    /**
     * Logout
     */
    public function logoutAction() {
        $this->getPluginManager()->get(Authenticate::NAME)->clearIdentity();
        $this->redirect()->toRoute('login');
        return $this->response;
    }

    /**
     * Main overview
     */
    public function indexAction() {
        $oViewModel = new ViewModel;
        $oViewModel->mode = 'list';

        $this->getPluginManager()->get(TicketService::NAME)->init($oViewModel);

        return $oViewModel;
    }

    /**
     *
     */
    public function dashboardAction() {
        $oViewModel = new ViewModel;
        $oViewModel->mode = 'dashboard';

        $this->getPluginManager()->get(TicketService::NAME)->init($oViewModel);

        $oViewModel->setTemplate('index');
        return $oViewModel;
    }

    /**
     *
     */
    public function teamAction() {
        $oViewModel = new ViewModel;
        $oViewModel->mode = 'team';

        $this->getPluginManager()->get(TicketService::NAME)->init($oViewModel);
        return $oViewModel;
    }

    /**
     *
     */
    public function teamdashAction() {
        $oViewModel = new ViewModel;
        $oViewModel->mode = 'team';

        $this->getPluginManager()->get(TicketService::NAME)->init($oViewModel);
        return $oViewModel;
    }

    /**
     *
     */
    public function conflictAction() {
        $oViewModel = new ViewModel;
        $oViewModel->mode = 'dashboard';

        $aTickets = $this->getPluginManager()->get(TicketService::NAME)->init($oViewModel)->getService()->getAllBugs();
        $oConstraintManager = new \Flightzilla\Model\Ticket\Integrity\Manager();
        $oViewModel->aStack = $oConstraintManager->check($aTickets);

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
     * Show ticket-details directly in flightzilla
     */
    public function detailAction() {
        $oViewModel = new ViewModel;
        $oViewModel->setTerminal(true);

        $sTicket = $this->params()->fromPost('ticket');
        if (empty($sTicket) !== true) {
            $oViewModel->oTicket = $this->getPluginManager()->get(TicketService::NAME)->getService()->getBugById($sTicket);
        }

        return $oViewModel;
    }

    /**
     *
     */
    public function goBugzillaAction() {
        $params = implode(',', $this->params()->fromQuery('id'));
        $this->redirect()->toUrl($this->getServiceLocator()->get('_serviceConfig')->bugzilla->baseUrl . '/buglist.cgi?quicksearch=' . $params);
        exit();
    }
}

