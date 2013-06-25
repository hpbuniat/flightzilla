<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ich
 * Date: 17.06.13
 * Time: 19:28
 * To change this template use File | Settings | File Templates.
 */

namespace Flightzilla\Controller;

use Flightzilla\Model\Watchlist;
use Zend\Mvc\Controller\AbstractActionController,
    Zend\View\Model\ViewModel,
    Flightzilla\Controller\Plugin\TicketService;



class WatchlistController extends AbstractActionController{

    /**
     * Show tickets on watchlist.
     *
     * @return ViewModel
     */
    public function indexAction()
    {

        $oViewModel = new ViewModel;
        $oViewModel->mode = 'list';

        $oTicketService =  $this->getPluginManager()->get(TicketService::NAME)->getService();
        $oViewModel->oTicketService = $oTicketService;

        $oServiceLocator = $this->getServiceLocator();
        $oWatchlist = new Watchlist\WatchlistService($oServiceLocator->get('_serviceConfig'), $oServiceLocator->get('Zend\Db\Adapter\Adapter'), $oServiceLocator->get('_auth'), $oTicketService);
        $oViewModel->watchlist = $oWatchlist->get();

        return $oViewModel;
    }


    public function addAction()
    {
        echo '<pre>';
        echo __FILE__ . ':' . __LINE__ . PHP_EOL;
        print_r($this->getRequest()->getPost());
        echo '</pre>';
    }

    public function removeAction()
    {

    }

}