<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ich
 * Date: 25.06.13
 * Time: 08:03
 * To change this template use File | Settings | File Templates.
 */

namespace Flightzilla\Model\Watchlist\Mapper;
use Flightzilla\Model\Watchlist;


/**
 * Class Xml
 * @package Flightzilla\Model\Watchlist\Mapper
 */
class Xml implements MapperInterface
{
    /**
     * @var string
     */
    private  $_sXmlFile = '';


    /**
     * @var string
     */
    private $_sUser;

    /**
     * @param \Watchlist
     */
    private $_oWatchlist = null;

    /**
     * @var \XMLWriter
     */
    private $_oXmlWriter;


    private $_oXmlReader;

    /**
     * @var \Zend\Config\Config
     */
    private $_oConfig;

    public function __construct($sUser, \Zend\Config\Config $oConfig)
    {
        $this->_sUser = $sUser;
        $this->_oConfig = $oConfig;
        $this->_oWatchlist = new Watchlist($this->_sUser);
        $this->_sXmlFile = $this->_oConfig->watchlist->persistenceOptions->xml->path . $this->_sUser . '.xml';
    }

    public function add($iTicket)
    {
        $this->get();
        $this->_oWatchlist->add($iTicket);

        return $this->_save();
    }

    public function remove($iTicket)
    {
        $this->get();
        $this->_oWatchlist->remove($iTicket);

        return $this->_save();

    }

    /**
     * @return Watchlist
     */
    public function get()
    {
        $oXml = @simplexml_load_file($this->_sXmlFile);
        if (true === $oXml instanceof \SimpleXMLElement){
            $aIds = $oXml->xpath('/watchlist/t.sari/ticket/@id');

           foreach ($aIds as $id){
              $this->_oWatchlist->add((int) $id);
           }
        }

        return $this->_oWatchlist;
    }

    /**
     * Save watchlist as xml.
     *
     * @return $this
     */
    private function _save()
    {
        $this->_oXmlWriter = new \XMLWriter();
        $this->_oXmlWriter->openUri($this->_sXmlFile);
        $this->_oXmlWriter->startDocument('1.0', 'utf-8');

        $this->_oXmlWriter->startElement('watchlist');
        $this->_oXmlWriter->startElement($this->_sUser);

        foreach ($this->_oWatchlist->get() as $ticketId){
            $this->_oXmlWriter->startElement('ticket');
            $this->_oXmlWriter->writeAttribute('id', (string) $ticketId);
            $this->_oXmlWriter->endElement();

        }

        $this->_oXmlWriter->endElement();
        $this->_oXmlWriter->endElement();
        $this->_oXmlWriter->endDocument();


        $this->_oXmlWriter->flush();

        return $this;
    }

}