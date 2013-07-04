<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ich
 * Date: 23.06.13
 * Time: 16:26
 * To change this template use File | Settings | File Templates.
 */

namespace Flightzilla\Model;


class Watchlist {

    private $_sUser;
    private $_aTickets = array();

    public function __construct($sUser){
        $this->_sUser = $sUser;
    }

    public function add($iTicket)
    {
        $this->_aTickets[$iTicket] = $iTicket;
        return $this;
    }

    public function remove($iTicket)
    {
        unset($this->_aTickets[$iTicket]);
        return $this;
    }

    public function get()
    {
        return $this->_aTickets;
    }

}