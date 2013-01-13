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
class Stack {

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
     * The parsed revisions
     *
     * @var array
     */
    protected $_aRevisions = array();

    /**
     * Switch to get the revisions as string
     *
     * @var boolean
     */
    const REVISIONS_AS_STRING = true;

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
        if ($bAsString === true) {
            return implode(',', $this->_aRevisions);
        }

        return $this->_aRevisions;
    }

    /**
     * Set the raw result
     *
     * @param  string $sXml
     *
     * @return Stack
     */
    public function setRaw($sXml) {
        $aMatches = array();
        preg_match('!<tickets>.*?</tickets>!i', $sXml, $aMatches);
        if (empty($aMatches[0]) !== true) {
            $this->_oXml = simplexml_load_string($aMatches[0]);
            $this->_parse();
        }

        return $this;
    }

    /**
     * Parse the result
     *
     * @return Stack
     */
    protected function _parse() {
        $this->_aRevisions = array();
        $aRevs = $this->_oXml->xpath('ticket');
        if (empty($aRevs) !== true) {
            foreach ($aRevs as $oRev) {
                $this->_aRevisions[] = (string) $oRev['rev'];
            }

            $this->_aRevisions = array_unique($this->_aRevisions);
            sort($this->_aRevisions);
        }

        return $this;
    }

}
