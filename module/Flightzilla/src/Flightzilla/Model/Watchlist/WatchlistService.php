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
namespace Flightzilla\Model\Watchlist;

use Flightzilla\Model\Watchlist;
use Flightzilla\Model\Watchlist\Mapper;
use Flightzilla\Model\Watchlist\Mapper\MapperInterface;

/**
 * Provide CRUD-methods for a specific persistence
 *
 * @author Tibor Sari
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class WatchlistService {

    /**
     * Known persistence-adapters
     *
     * @var string
     */
    const PERSISTENCE_TYPE_SQLITE = 'sqlite';

    const PERSISTENCE_TYPE_XML = 'xml';

    /**
     * The persistence-mapper
     *
     * @var MapperInterface
     */
    private $_oMapper;

    /**
     * The watchlist instance
     *
     * @var Watchlist
     */
    private $_oWatchlist;

    /**
     * The ticket-service
     *
     * @var \Flightzilla\Model\Ticket\AbstractSource
     */
    private $_oTicketService;

    /**
     * The auth-adapter
     *
     * @var \Flightzilla\Authentication\Adapter
     */
    private $_oAuth;

    /**
     * The current configuration
     *
     * @var \Zend\Config\Config
     */
    private $_oModuleConfig;

    /**
     * Create the watchlist-service
     *
     * @TODO Current auth object and persist layer
     *
     * @param \Zend\Config\Config $oModuleConfig
     * @param \Flightzilla\Authentication\Adapter $oAuth
     * @param \Flightzilla\Model\Ticket\AbstractSource $oTicketService
     */
    public function __construct(\Zend\Config\Config $oModuleConfig, \Flightzilla\Authentication\Adapter $oAuth, \Flightzilla\Model\Ticket\AbstractSource $oTicketService) {

        $this->_oAuth = $oAuth;
        $this->_oModuleConfig = $oModuleConfig;
        $this->_oTicketService = $oTicketService;
        $this->_oWatchlist = new Watchlist($this->_oAuth->getLogin());
    }

    /**
     * @return Mapper\MapperInterface|Mapper\Sqlite
     * @throws Exception
     */
    private function _getMapper() {

        if (($this->_oMapper instanceof Mapper\MapperInterface) !== true) {
            switch ($this->_oModuleConfig->watchlist->persistenceType) {
                case WatchlistService::PERSISTENCE_TYPE_SQLITE:
                    // Todo
                    break;

                case WatchlistService::PERSISTENCE_TYPE_XML:
                    $this->_oMapper = new Mapper\Xml($this->_oAuth->getLogin(), $this->_oModuleConfig);
                    break;

                default:
                    throw new Exception(Exception::UNKNOWN_PERSISTENCE_TYPE);
            }
        }

        return $this->_oMapper;
    }

    /**
     * Add a ticket number to an existing watchlist
     *
     * @param  int $iTicket
     *
     * @return $this
     */
    public function add($iTicket) {

        $this->_getMapper()->add($iTicket);
        return $this;
    }

    /**
     * Remove a ticket from watchlist.
     *
     * @param  int $iTicket
     *
     * @return $this
     */
    public function remove($iTicket) {

        $this->_getMapper()->remove($iTicket);
        return $this;
    }

    /**
     * Get the watchlist
     *
     * @return array
     */
    public function get() {

        $oWatchlist = $this->_getMapper()->get();
        $aTickets = $oWatchlist->get();

        $aWatchedTickets = $this->_oTicketService->getBugListByIds($aTickets);
        foreach ($aWatchedTickets as $oWatched) {
            $oWatched->setOnWatchlist(true);
        }

        $aWatchlist = array(
            'aProjects' => $this->_oTicketService->getProjectsFromIds($aTickets),
            'aWatchlist' => $aWatchedTickets
        );

        ksort($aWatchlist);

        //@TODO Create a status overview for the sidebar

        /*    $oView->iTotal = $oTicketService->getCount();
            $oView->aStats = $oTicketService->getStats()->getWorkflowStats();
            $oView->aStatuses = $oTicketService->getStats()->getStatuses();
            $oView->aPriorities = $oTicketService->getStats()->getPriorities();
            $oView->aSeverities = $oTicketService->getStats()->getSeverities();*/

        return $aWatchlist;
    }
}
