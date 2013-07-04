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

namespace Flightzilla\Model\Watchlist\Mapper;
use Flightzilla\Model\Watchlist;

/**
 * Xml-Mapper for the Watchlist-Service
 *
 * @author Tibor Sari
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Xml implements MapperInterface {

    /**
     * The xml-filename
     *
     * @var string
     */
    private $_sXmlFile = '';

    /**
     * The current user
     *
     * @var string
     */
    private $_sUser;

    /**
     * The watchlist-instance
     *
     * @param \Watchlist
     */
    private $_oWatchlist = null;

    /**
     * The current configuration
     *
     * @var \Zend\Config\Config
     */
    private $_oConfig;

    /**
     * Create the xml-mapper
     *
     * @param string $sUser
     * @param \Zend\Config\Config $oConfig
     */
    public function __construct($sUser, \Zend\Config\Config $oConfig) {

        $this->_sUser = $sUser;
        $this->_oConfig = $oConfig;
        $this->_oWatchlist = new Watchlist($this->_sUser);
        $this->_sXmlFile = $this->_oConfig->watchlist->persistenceOptions->xml->path . $this->_sUser . '.xml';
    }

    /**
     * (non-PHPdoc)
     * @see MapperInterface::add()
     */
    public function add($iTicket) {

        $this->get();
        $this->_oWatchlist->add($iTicket);

        return $this->_save();
    }

    /**
     * (non-PHPdoc)
     * @see MapperInterface::remove()
     */
    public function remove($iTicket) {

        $this->get();
        $this->_oWatchlist->remove($iTicket);

        return $this->_save();

    }

    /**
     * (non-PHPdoc)
     * @see MapperInterface::get()
     */
    public function get() {

        $oXml = @simplexml_load_file($this->_sXmlFile);
        if (true === $oXml instanceof \SimpleXMLElement) {
            $aIds = $oXml->xpath(sprintf('/watchlist/%s/ticket/@id', $this->_sUser));
            foreach ($aIds as $id) {
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
    private function _save() {

        $oXmlWriter = new \XMLWriter();
        $oXmlWriter->openUri($this->_sXmlFile);
        $oXmlWriter->startDocument('1.0', 'utf-8');

        $oXmlWriter->startElement('watchlist');
        $oXmlWriter->startElement($this->_sUser);

        foreach ($this->_oWatchlist->get() as $ticketId) {
            $oXmlWriter->startElement('ticket');
            $oXmlWriter->writeAttribute('id', (string) $ticketId);
            $oXmlWriter->endElement();

        }

        $oXmlWriter->endElement();
        $oXmlWriter->endElement();
        $oXmlWriter->endDocument();

        $oXmlWriter->flush();

        return $this;
    }

}
