<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ich
 * Date: 23.06.13
 * Time: 16:05
 * To change this template use File | Settings | File Templates.
 */

namespace Flightzilla\Model\Watchlist\Mapper;
use Flightzilla\Model\Watchlist;
use Zend\Db\Adapter\Driver\ResultInterface,
    Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;

/**
 * Mappt zwischen der Entität und der Persistenzschicht
 *
 *
 * Class Sqlite
 * @package Flightzilla\Model\Watchlist
 */
class Sqlite implements MapperInterface{

    /**
     * @param \Watchlist
     */
    private $_oWatchlist = null;

    private $_oDb = null;

    private $_sUser;

    public function __construct(\Zend\Db\Adapter\Adapter $oDb, $sUser)
    {
        $this->_sUser = $sUser;
        $this->_oDb = $oDb;
        $this->_oWatchlist = new Watchlist($sUser);
    }

    public function add($iTicket)
    {

    }

    public function remove($iTicket)
    {

    }

    /**
     * @return null
     */
    public function get()
    {
        $sql = 'SELECT * from tickets t INNER JOIN  user u on t.userId = u.Id WHERE u.name = ?';

        $oSql = new Sql($this->_oDb);


        $result = $this->_oDb->query($sql, array($this->_sUser));

        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $resultSet = new ResultSet;
            $resultSet->initialize($result);

            echo '<pre>';
            echo __FILE__ . ':' . __LINE__ . PHP_EOL;
            print_r('foo');
            echo '</pre>';

            foreach ($resultSet as $row) {
                echo $row->my_column . PHP_EOL;
            }
        }

        $this->_oWatchlist->add(169846);

        return $this->_oWatchlist;
    }


}