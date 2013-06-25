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
        $oWatchlist = new Watchlist\WatchlistService($oServiceLocator->get('_serviceConfig'), $oServiceLocator->get('_auth'), $oTicketService);
        $oViewModel->watchlist = $oWatchlist->get();

        return $oViewModel;
    }


    public function addAction()
    {
        $filter = new \Zend\Filter\Digits();
        $ticketId = $filter->filter($this->getRequest()->getPost()->ticketId);

        $oTicketService =  $this->getPluginManager()->get(TicketService::NAME)->getService();
        $oServiceLocator = $this->getServiceLocator();
        $oWatchlist = new Watchlist\WatchlistService($oServiceLocator->get('_serviceConfig'),$oServiceLocator->get('_auth'), $oTicketService);
        $oWatchlist->add($ticketId);

        $this->redirect()->toRoute('flightzilla', array('controller' => 'watchlist'));
    }

    public function removeAction()
    {
        $filter = new \Zend\Filter\Digits();
        $ticketId = $filter->filter($this->getRequest()->getPost()->ticketId);

        $oTicketService =  $this->getPluginManager()->get(TicketService::NAME)->getService();
        $oServiceLocator = $this->getServiceLocator();
        $oWatchlist = new Watchlist\WatchlistService($oServiceLocator->get('_serviceConfig'),$oServiceLocator->get('_auth'), $oTicketService);
        $oWatchlist->remove($ticketId);

        $this->redirect()->toRoute('flightzilla', array('controller' => 'watchlist'));
    }

}