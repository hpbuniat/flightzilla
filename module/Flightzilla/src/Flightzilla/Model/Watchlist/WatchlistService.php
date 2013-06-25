<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ich
 * Date: 17.06.13
 * Time: 19:41
 * To change this template use File | Settings | File Templates.
 */

namespace Flightzilla\Model\Watchlist;
use Flightzilla\Model\Watchlist\Mapper;

/**
 * Class WatchlistService
 * @package Flightzilla\Model\Watchlist
 *
 * Nimm eine Persistenzinstanz auf und führe ein paar CRUD Befehle aus.
 */
class WatchlistService {


    const PERSISTENCE_TYPE_SQLITE    = 'sqlite';
    const PERSISTENCE_TYPE_XML       = 'xml';

    /**
     * @var MapperInterface
     */
    private $_oMapper;

    /**
     * @var Watchlist
     */
    private $_oWatchlist;

    /**
     * @var \Flightzilla\Model\Ticket\AbstractSource
     */
    private $_oTicketService;

    /**
     * @var \Flightzilla\Authentication\Adapter
     */
    private $_oAuth;

    /**
     * @var \Zend\Config\Config
     */
    private $_oModuleConfig;

    /**
     * @todo Current auth object and persist layer
     *
     * @param \Zend\Config\Config $oModuleConfig
     * @param \Zend\Db\Adapter\Adapter $oDb
     * @param \Flightzilla\Authentication\Adapter $oAuth
     * @param \Flightzilla\Model\Ticket\AbstractSource $oTicketService
     */
    public function __construct(\Zend\Config\Config $oModuleConfig, \Flightzilla\Authentication\Adapter $oAuth, \Flightzilla\Model\Ticket\AbstractSource $oTicketService)
    {
        $this->_oAuth = $oAuth;
        $this->_oModuleConfig = $oModuleConfig;
        $this->_oTicketService = $oTicketService;
        $this->_oWatchlist = new \Flightzilla\Model\Watchlist($this->_oAuth->getLogin());
    }

    /**
     * @return Mapper\MapperInterface|Mapper\Sqlite
     * @throws Exception
     */
    private function _getMapper()
    {
        if (!$this->_oMapper instanceof Mapper\MapperInterface){
            switch ($this->_oModuleConfig->watchlist->persistenceType){
                case WatchlistService::PERSISTENCE_TYPE_SQLITE:
                    // Todo
                    break;
                case WatchlistService::PERSISTENCE_TYPE_XML:
                    $this->_oMapper = new Mapper\Xml($this->_oAuth->getLogin(), $this->_oModuleConfig);
                    break;
                default:
                    throw new Exception(Exception::UNKNOWN_PERSISISTENCE_TYPE);
            }
        }

        return $this->_oMapper;
    }

    /**
     * Add a ticket number to an existing watchlist
     *
     * @param $iTicket
     * @return $this
     */
    public function add($iTicket)
    {
        $this->_getMapper()->add($iTicket);
        return $this;
    }

    /**
     * Remove a ticket from watchlist.
     *
     * @param $iTicket
     * @return $this
     */
    public function remove($iTicket)
    {
        $this->_getMapper()->remove($iTicket);
        return $this;
    }

    /**
     * @return array
     */
    public function get()
    {
        $oWatchlist = $this->_getMapper()->get();

        return $this->_oTicketService->getBugListByIds($oWatchlist->get());
    }
}