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

/**
 * View-Helper to annotate tickets with workflow-related classes
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
namespace Flightzilla\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Flightzilla\Model\Ticket\Type\Bug;

class Workflow extends AbstractHelper {

    /**
     * The ticket-config
     *
     * @var \Zend\Config\Config
     */
    protected $_oConfig;

    /**
     * Create the view-helper
     *
     * @param \Zend\Config\Config $oConfig
     */
    public function __construct(\Zend\Config\Config $oConfig) {
        $this->_oConfig = $oConfig;
    }

    /**
     * Get the workflow-stats of the bug
     *
     * @param  Bug $oBug
     *
     * @return string
     */
    public function __invoke(Bug $oBug) {

        $sClasses = sprintf('prio%s ', $oBug->priority);
        $sClasses .= sprintf('severity%s ', $oBug->bug_severity);

        if ($oBug->isQuickOne() === true) {
            $sClasses .= Bug::WORKFLOW_QUICK . ' ';
        }

        if ($oBug->isFailed() === true) {
            $sClasses .= Bug::WORKFLOW_FAILED . ' ';
        }

        if ($oBug->isMergeable() === true) {
            $sClasses .= Bug::WORKFLOW_MERGE . ' ';
        }

        if ($oBug->isWorkedOn()) {
            $sClasses .= Bug::WORKFLOW_INPROGRESS . ' ';
        }

        if ($oBug->isActive()) {
            $sClasses .= Bug::WORKFLOW_ACTIVE . ' ';
        }

        if ($oBug->isOnlyTranslation() === true) {
            $sClasses .= Bug::WORKFLOW_TRANSLATION . ' ';
        }

        if ($oBug->hasFlag(Bug::FLAG_SCREEN, '?') === true) {
            $sClasses .= Bug::WORKFLOW_SCREEN . ' ';
        }

        if ($oBug->hasFlag(Bug::FLAG_COMMENT, '?') === true or $oBug->getStatus() === Bug::STATUS_CLARIFICATION) {
            $sClasses .= Bug::WORKFLOW_COMMENT . ' ';
        }

        if ($oBug->hasFlag(Bug::FLAG_TESTING, '?') === true) {
            $sClasses .= Bug::WORKFLOW_TESTING . ' ';
        }

        if ($oBug->isChangedWithinLimit($this->_oConfig->tickets->workflow->timeout) !== true) {
            $sClasses .= Bug::WORKFLOW_TIMEDOUT . ' ';
        }

        return $sClasses;
    }
}
