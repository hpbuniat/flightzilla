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
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace Flightzilla\Model\Mergy\Revision;


/**
 * Provide a stack for revisions
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Stack implements \ArrayAccess, \Countable {

    /**
     * The name of the project
     *
     * @var string
     */
    protected $_sName;

    /**
     * The location for the stable-branch (trunk)
     *
     * @var string
     */
    protected $_sStable;

    /**
     * The location for the source- (feature-) branch
     *
     * @var string
     */
    protected $_sRemote;

    /**
     * The url for changesets
     *
     * @var string
     */
    protected $_sChangesetUrl;

    /**
     * The raw xml from mergy
     *
     * @var \SimpleXMLElement
     */
    protected $_oXml;

    /**
     * The parsed result
     *
     * @var array
     */
    protected $_aResult = array();

    /**
     * Switch to get the revisions as string
     *
     * @var boolean
     */
    const REVISIONS_AS_STRING = true;

    /**
     * Which attribute of a revision should be parsed as index
     *
     * @var string
     */
    const PARSE_REVISION = 'revision';
    const PARSE_TICKETS = 'tickets';

    /**
     * Create a Stack
     *
     * @param string $sName
     * @param \Zend\Config\Config $oSource
     */
    public function __construct($sName, \Zend\Config\Config $oSource) {
        $this->_sName = $sName;
        $this->_sRemote = $oSource->feature;
        $this->_sStable = $oSource->stable;
        $this->_sChangesetUrl = $oSource->trac;
    }

    /**
     * Get the name
     *
     * @return string
     */
    public function getName() {
        return $this->_sName;
    }

    /**
     * Get the remote
     *
     * @return string
     */
    public function getRemote() {
        return $this->_sRemote;
    }

    /**
     * Get the stable
     *
     * @return string
     */
    public function getStable() {
        return $this->_sStable;
    }

    /**
     * Check, if there are revisions listed
     *
     * @return boolean
     */
    public function isEmpty() {
        return empty($this->_aRevisions);
    }

    /**
     * Get the changeset-url for a ticket
     *
     * @param  int $iTicket
     *
     * @return string
     */
    public function getChangeset($iTicket) {
        return sprintf($this->_sChangesetUrl, $iTicket);
    }

    /**
     * Get the raw xml
     *
     * @return \SimpleXMLElement
     */
    public function getRaw() {
        return $this->_oXml;
    }

    /**
     * Get the revisions
     *
     * @param  boolean $bAsString
     *
     * @return string|array According to $bAsString
     */
    public function getRevisions($bAsString = false) {
        return ($bAsString === true) ? implode(',', $this->_aResult) : $this->_aResult;
    }

    /**
     * Set the raw result
     *
     * @param  string $sXml
     * @param  string $sParse
     *
     * @return Stack
     */
    public function setRaw($sXml, $sParse = self::PARSE_REVISION) {
        $this->_aResult = $aMatches = array();
        preg_match('!<tickets>.*?</tickets>!i', $sXml, $aMatches);
        if (empty($aMatches[0]) !== true) {
            $this->_oXml = simplexml_load_string($aMatches[0]);
            switch ($sParse) {
                case self::PARSE_REVISION:
                    $this->_parseRevision();
                    break;

                case self::PARSE_TICKETS;
                    $this->_parseTickets();
                    break;
            }
        }

        return $this;
    }

    /**
     * Parse the result to revisions
     *
     * @return $this
     */
    protected function _parseRevision() {
        $aRevs = $this->_oXml->xpath('ticket');
        if (empty($aRevs) !== true) {
            foreach ($aRevs as $oRev) {
                $this->_aResult = array_merge($this->_aResult, explode(',', (string) $oRev['rev']));
            }

            $this->_aResult = array_unique($this->_aResult);
            sort($this->_aResult);
        }

        return $this;
    }

    /**
     * Parse the result to ticket-numbers
     *
     * @return $this
     */
    protected function _parseTickets() {
        $aRevs = $this->_oXml->xpath('ticket');
        if (empty($aRevs) !== true) {
            foreach ($aRevs as $oRev) {
                $aTickets = explode(',', (string) $oRev['id']);
                $aRevisions = explode(',', (string) $oRev['rev']);
                foreach ($aTickets as $iTicket) {
                    if (empty($this->_aResult[$iTicket]) === true) {
                        $this->_aResult[$iTicket] = array();
                    }

                    foreach ($aRevisions as $iRevision) {
                        $this->_aResult[$iTicket][$iRevision] = (string) $oRev['author'];
                    }
                }
            }

            ksort($this->_aResult);
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \Countable::count()
     */
    public function count() {
        return count($this->_aResult);
    }

    /**
     * (non-PHPdoc)
     * @see \ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value) {
        if (is_null($offset) === true) {
            $this->_aResult[] = $value;
        }
        else {
            $this->_aResult[$offset] = $value;
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \ArrayAccess::offsetExists()
     */
    public function offsetExists($offset) {
        return isset($this->_aResult[$offset]);
    }

    /**
     * (non-PHPdoc)
     * @see \ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset) {
        unset($this->_aResult[$offset]);
        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \ArrayAccess::offsetGet()
     */
    public function offsetGet($offset) {
        return ($this->offsetExists($offset) === true) ? $this->_aResult[$offset] : null;
    }
}
