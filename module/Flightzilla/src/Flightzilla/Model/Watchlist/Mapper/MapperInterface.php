<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ich
 * Date: 23.06.13
 * Time: 19:15
 * To change this template use File | Settings | File Templates.
 */

namespace Flightzilla\Model\Watchlist\Mapper;


interface MapperInterface {

    public function add($iTicket);

    public function remove($iTicket);

    public function get();

}