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
 * @author Tibor Sari
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace Flightzilla\Controller;

use Flightzilla\Model\Watchlist;
use Zend\Mvc\Controller\AbstractActionController,
    Zend\View\Model\ViewModel,
    Flightzilla\Controller\Plugin\TicketService;

/**
 * Controller for watchlist-interaction
 *
 * @TODO drag and drop for add & remove
 * @TODO print view for projects or tickets
 *
 * @author Tibor Sari
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class WatchlistController extends AbstractActionController {

    /**
     * Show tickets on watchlist.
     *
     * @return ViewModel
     */
    public function indexAction() {

        $oViewModel = new ViewModel;
        $oViewModel->mode = 'list';

        $oTicketService = $this->getPluginManager()->get(TicketService::NAME)->init($oViewModel)->getService();
        $oViewModel->oTicketService = $oTicketService;

        $oServiceLocator = $this->getServiceLocator();
        $oWatchlist = new Watchlist\WatchlistService($oServiceLocator->get('_serviceConfig'), $oServiceLocator->get('_auth'), $oTicketService);
        foreach ($oWatchlist->get() as $type => $value) {
            $oViewModel->{$type} = $value;
        }

        return $oViewModel;
    }

    /**
     * Add a ticket to the watchlist
     *
     * @return void
     */
    public function addAction() {

        $filter = new \Zend\Filter\Digits();
        $ticketId = $filter->filter($this->getRequest()->getPost()->ticketId);

        $oTicketService = $this->getPluginManager()->get(TicketService::NAME)->getService();
        $oServiceLocator = $this->getServiceLocator();
        $oWatchlist = new Watchlist\WatchlistService($oServiceLocator->get('_serviceConfig'), $oServiceLocator->get('_auth'), $oTicketService);
        $oWatchlist->add($ticketId);

        $this->redirect()->toRoute('flightzilla', array('controller' => 'watchlist'));
    }

    /**
     * Remove a ticket from the watchlist
     *
     * @return void
     */
    public function removeAction() {

        $filter = new \Zend\Filter\Digits();
        $ticketId = $filter->filter($this->getRequest()->getPost()->ticketId);

        $oTicketService = $this->getPluginManager()->get(TicketService::NAME)->getService();
        $oServiceLocator = $this->getServiceLocator();
        $oWatchlist = new Watchlist\WatchlistService($oServiceLocator->get('_serviceConfig'), $oServiceLocator->get('_auth'), $oTicketService);
        $oWatchlist->remove($ticketId);

        $this->redirect()->toRoute('flightzilla', array('controller' => 'watchlist'));
    }
}
