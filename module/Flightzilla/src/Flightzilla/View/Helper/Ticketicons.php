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

/**
 * View-Helper to create status-related flags
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
namespace Flightzilla\View\Helper;
use Zend\View\Helper\AbstractHelper;

class Ticketicons extends AbstractHelper {

    /**
     * Get the workflow-stats of the bug
     *
     * @param  \Flightzilla\Model\Ticket\Type\Bug $oBug
     *
     * @return string
     */
    public function __invoke(\Flightzilla\Model\Ticket\Type\Bug $oBug) {

        $sClasses = '';
        if ($oBug->hasFlag(\Flightzilla\Model\Ticket\Type\Bug::FLAG_COMMENT, '?')) {
            $sClasses .= '&nbsp;<span class="ui-silk ui-silk-comment" title="' . \Flightzilla\Model\Ticket\Type\Bug::FLAG_COMMENT . '">&nbsp;</span>';
            if (strlen($oBug->commentrequest_user) > 0) {
                $sClasses .= '<span class="red"> ' . $oBug->commentrequest_user . '</span>';
            }
        }

        if ($oBug->hasFlag(\Flightzilla\Model\Ticket\Type\Bug::FLAG_TRANSLATION, '+')) {
            $sClasses .= '&nbsp;<span class="red">i18n</span>';
        }

        if ($oBug->hasFlag(\Flightzilla\Model\Ticket\Type\Bug::FLAG_SCREEN, '+')) {
            $sClasses .= '&nbsp;<span class="ui-silk ui-silk-thumb-up" title="' . \Flightzilla\Model\Ticket\Type\Bug::FLAG_SCREEN . '">&nbsp;</span>';
        }

        if ($oBug->hasFlag(\Flightzilla\Model\Ticket\Type\Bug::FLAG_DBCHANGE, '+')) {
            $sClasses .= '&nbsp;<span class="ui-silk ui-silk-database-refresh" title="' . \Flightzilla\Model\Ticket\Type\Bug::FLAG_DBCHANGE . '">&nbsp;</span>';
        }

        if ($oBug->hasFlag(\Flightzilla\Model\Ticket\Type\Bug::FLAG_TESTING, '?') === true) {
            $sClasses .= '&nbsp;<span class="ui-silk ui-silk-magnifier" title="' . \Flightzilla\Model\Ticket\Type\Bug::FLAG_TESTING . '">&nbsp;</span>';
            if (strlen($oBug->testingrequest_user) > 0) {
                $sClasses .= '<span class="red"> ' . $oBug->testingrequest_user . '</span>';
            }
        }

        if ($oBug->isType(\Flightzilla\Model\Ticket\Type\Bug::TYPE_BUG) === true) {
            $sClasses .= '&nbsp;<span class="ui-silk ui-silk-bug" title="' . \Flightzilla\Model\Ticket\Type\Bug::TYPE_BUG . '">&nbsp;</span>';
        }

        return $sClasses;
    }
}
