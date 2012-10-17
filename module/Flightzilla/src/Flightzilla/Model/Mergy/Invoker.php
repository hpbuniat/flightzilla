<?php
/**
 * flightzilla
 *
 * Copyright (c)2012, Hans-Peter Buniat <hpbuniat@googlemail.com>.
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
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace Flightzilla\Model\Mergy;


/**
 * Call mergy and get unmerged revisions, according to selection
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Invoker {

    /**
     * Command-Wrapper
     *
     * @var \Flightzilla\Model\Command
     */
    protected $_oCommand;

    /**
     * Stack for the results
     *
     * @var array
     */
    protected $_aStack = array();

    /**
     * The commands return string
     *
     * @var string
     */
    protected $_sCliOutput;

    /**
     * Mergy-Command to collect a list
     *
     * @var string
     */
    const LIST_COMMAND = '%s --remote=%s --path=%s --unattended --config=false --list --formatter=xml --ticket=%s';

    /**
     * Mergy command to merge
     *
     * @var string
     */
    const MERGE_COMMAND = 'export LC_CTYPE=de_DE.UTF-8; cd %s; %s --ticket=%s --strict --unattended --verbose %s --config=mergy.unattended.json';

    /**
     * The commit-trigger
     *
     * @var string
     */
    const MERGE_COMMIT = '--commit';

    /**
     * Message for success
     *
     * @var string
     */
    const MSG_SUCCESS = '<strong>mergy was successful</strong><br/>%s';

    /**
     * Message for error
     *
     * @var string
     */
    const MSG_ERROR = '<strong>mergy failed</strong><br/>%s<br/><br/>Check the logs & the wc for errors';

    /**
     * Message, if no tickets could be parsed
     *
     * @var string
     */
    const NO_TICKETS = 'No tickets found';

    /**
     * Create the mergy-invoker
     *
     * @param \Flightzilla\Model\Command $oCommand
     */
    public function __construct(\Flightzilla\Model\Command $oCommand) {
        $this->_oCommand = $oCommand;
    }

    /**
     * Fetch the revisions using mergy
     *
     * @param  \Flightzilla\Model\Mergy\Revision\Stack $oStack
     * @param  string $sMergy
     * @param  \Zend\Config\Config $oSource
     * @param  string $sTickets
     *
     * @return Invoker
     */
    public function mergelist(\Flightzilla\Model\Mergy\Revision\Stack $oStack, $sMergy, \Zend\Config\Config $oSource, $sTickets) {
        $sCommand = sprintf(self::LIST_COMMAND, $sMergy, $oSource->feature, $oSource->stable, $this->_parseTickets($sTickets));
        $this->_sCliOutput = trim($this->_oCommand->execute($sCommand)->get());

        if ($this->_oCommand->isSuccess() and empty($this->_sCliOutput) !== true) {
            $this->_aStack[$oStack->getName()] = $oStack->setRaw($this->_sCliOutput);
        }
        else {
            Zend_Registry::get('_Logger')->err($this->_oCommand->status() . ' ' . $this->_sCliOutput, __FILE__ . ':' . __LINE__);
        }

        return $this;
    }

    /**
     * Merge some tickets with mergy
     *
     * @param  string $sMergy
     * @param  \Zend\Config\Config $oSource
     * @param  string $sTickets
     * @param  boolean $bCommit
     *
     * @return Invoker
     */
    public function merge($sMergy, \Zend\Config\Config $oSource, $sTickets, $bCommit) {
        $sCommand = sprintf(self::MERGE_COMMAND, $oSource->wc, $sMergy, $this->_parseTickets($sTickets), (($bCommit !== false) ? self::MERGE_COMMIT : ''));
        $this->_sCliOutput = $sCommand . PHP_EOL . PHP_EOL . trim($this->_oCommand->execute($sCommand)->get());

        if ($this->_oCommand->isSuccess() !== true) {
            Zend_Registry::get('_Logger')->err($this->_oCommand->status() . ' ' . $this->_sCliOutput, __FILE__ . ':' . __LINE__);
        }

        return $this;
    }

    /**
     * Parse tickets of the param
     *
     * @param  string $sTickets
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function _parseTickets($sTickets) {
        $aMatch = array();
        preg_match_all('!\d+!', $sTickets, $aMatch);
        if (empty($aMatch[0]) !== true) {
            return implode(',', $aMatch[0]);
        }

        throw new \InvalidArgumentException(self::NO_TICKETS);
    }

    /**
     * Get the result-message
     *
     * @return string
     */
    public function getMessage() {
        $sMessage = ($this->isSuccess() === true) ? self::MSG_SUCCESS : self::MSG_ERROR;
        return sprintf($sMessage, $this->getCommand());
    }

    /**
     * Get the last executed command
     *
     * @return string
     */
    public function getCommand() {
        return $this->_oCommand->getCommand();
    }

    /**
     * Check, if the last command was successful
     *
     * @return boolean
     */
    public function isSuccess() {
         return $this->_oCommand->isSuccess();
    }

    /**
     * Get the output
     *
     * @return string
     */
    public function getOutput() {
        return $this->_sCliOutput;
    }

    /**
     * Get the mergy-result stack
     *
     * @return array
     */
    public function getStack() {
        return $this->_aStack;
    }
}