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
* @license http://www.opensource.org/licenses/bsd-license.php BSD License
*/

/**
 * View-Helper to annotate tickets with workflow-related classes
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class View_Helper_Workflow extends Zend_View_Helper_Abstract {

    /**
     * Get the workflow-stats of the bug
     *
     * @param  Model_Ticket_Type_Bug $oBug
     *
     * @return string
     */
    public function workflow(Model_Ticket_Type_Bug $oBug) {
        $sClasses = sprintf('prio%s ', $oBug->priority);
        $sClasses .= sprintf('severity%s ', $oBug->bug_severity);

        if ($oBug->isQuickOne() === true) {
            $sClasses .= Model_Ticket_Type_Bug::WORKFLOW_QUICK . ' ';
        }

        if ($oBug->isFailed() === true) {
            $sClasses .= Model_Ticket_Type_Bug::WORKFLOW_FAILED . ' ';
        }

        if ($oBug->isMergeable() === true) {
            $sClasses .= Model_Ticket_Type_Bug::WORKFLOW_MERGE . ' ';
        }

        if ($oBug->isWorkedOn()) {
            $sClasses .= Model_Ticket_Type_Bug::WORKFLOW_INPROGRESS . ' ';
        }

        if ($oBug->isActive()) {
            $sClasses .= Model_Ticket_Type_Bug::WORKFLOW_ACTIVE . ' ';
        }

        if ($oBug->isOnlyTranslation() === true) {
            $sClasses .= Model_Ticket_Type_Bug::WORKFLOW_TRANSLATION . ' ';
        }

        if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_SCREEN, '?') === true) {
            $sClasses .= Model_Ticket_Type_Bug::WORKFLOW_SCREEN . ' ';
        }

        if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_COMMENT, '?') === true) {
            $sClasses .= Model_Ticket_Type_Bug::WORKFLOW_COMMENT . ' ';
        }

        if ($oBug->isChangedWithinLimit(Zend_Registry::get('_Config')->model->tickets->workflow->timeout) !== true) {
            $sClasses .= Model_Ticket_Type_Bug::WORKFLOW_TIMEDOUT . ' ';
        }

        return $sClasses;
    }
}