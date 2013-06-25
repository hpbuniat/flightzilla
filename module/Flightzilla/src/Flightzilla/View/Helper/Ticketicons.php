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
 * View-Helper to create status-related flags
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
use Flightzilla\Model\Ticket\Source\Bugzilla;

class Ticketicons extends AbstractHelper {

    /**
     * Constants for icons
     *
     * @var string
     */
    const ICON_CHECKED = 'icon-thumbs-up';
    const ICON_RESOLVED = 'icon-ok-circle';
    const ICON_TESTING = 'icon-eye-open';
    const ICON_COMMENT = 'icon-comment';
    const ICON_REVENUE = 'icon-tags';
    const ICON_WARNING = 'icon-warning-sign';
    const ICON_UPDATE = 'icon-refresh';

    /**
     * Get the workflow-stats of the bug
     *
     * @param  Bug $oBug
     *
     * @return string
     */
    public function __invoke(Bug $oBug) {

        $sClasses = '';
        if ($oBug->isStatusAtLeast(Bug::STATUS_RESOLVED) === true) {
            if ($oBug->hasFlag(Bug::FLAG_TESTING, Bugzilla::BUG_FLAG_GRANTED) === true) {
                $sClasses .= sprintf('&nbsp;<i class="%s"></i>', self::ICON_CHECKED);
            }
            else {
                $sClasses .= sprintf('&nbsp;<i class="%s"></i>', self::ICON_RESOLVED);
            }
        }

        if ($oBug->hasFlag(Bug::FLAG_COMMENT, Bugzilla::BUG_FLAG_REQUEST) === true or $oBug->getStatus() === Bug::STATUS_CLARIFICATION) {
            $sClasses .= sprintf('&nbsp;<i class="%s" title="Awaiting %s">&nbsp;</i>', self::ICON_COMMENT, Bug::FLAG_COMMENT);
            if (strlen($oBug->commentrequest_user) > 0) {
                $sClasses .= '<span class="red"> ' . $oBug->commentrequest_user . '</span>';
            }
        }

        if ($oBug->hasFlag(Bug::FLAG_TRANSLATION, Bugzilla::BUG_FLAG_GRANTED) === true) {
            $sClasses .= '&nbsp;<span class="red">i18n</span>';
        }

        if ($oBug->hasFlag(Bug::FLAG_SCREEN, Bugzilla::BUG_FLAG_GRANTED) === true and $oBug->hasFlag(Bug::FLAG_SCREEN, Bugzilla::BUG_FLAG_REQUEST) === false) {
            $sClasses .= sprintf('&nbsp;<i class="%s" title="%s">&nbsp;</i>', self::ICON_CHECKED, Bug::FLAG_SCREEN);
        }
        elseif ($oBug->hasFlag(Bug::FLAG_SCREEN, Bugzilla::BUG_FLAG_REQUEST) === true) {
            $sClasses .= sprintf('&nbsp;<i class="%s" title="Awaiting %s">&nbsp;</i>', self::ICON_TESTING, Bug::FLAG_SCREEN);
        }

        if ($oBug->hasFlag(Bug::FLAG_DBCHANGE, Bugzilla::BUG_FLAG_GRANTED) === true) {
            $sClasses .= '&nbsp;<span class="ui-silk ui-silk-database-refresh" title="' . Bug::FLAG_DBCHANGE . '">&nbsp;</span>';
        }

        if ($oBug->hasFlag(Bug::FLAG_TESTING, Bugzilla::BUG_FLAG_REQUEST) === true) {
            $sClasses .= sprintf('&nbsp;<i class="%s" title="Awaiting %s">&nbsp;</i>', self::ICON_TESTING, Bug::FLAG_TESTING);
            if (strlen($oBug->testingrequest_user) > 0) {
                $sClasses .= '<span class="red"> ' . $oBug->testingrequest_user . '</span>';
            }
        }

        $sRevenue = $oBug->getRevenue();
        if (empty($sRevenue) !== true) {
            $sClasses .= sprintf('&nbsp;<i class="%s" title="%s">&nbsp;</i>', self::ICON_REVENUE, $sRevenue);
        }

        if ($oBug->hasFlag(Bug::FLAG_TESTSERVER, Bugzilla::BUG_FLAG_REQUEST) === true) {
            $sClasses .= sprintf('&nbsp;<i class="%s" title="Awaiting %s">&nbsp;</i>', self::ICON_UPDATE, Bug::FLAG_TESTSERVER);
        }

        if ($oBug->isType(Bug::TYPE_BUG) === true) {
            $sClasses .= '&nbsp;<span class="ui-silk ui-silk-bug" title="' . Bug::TYPE_BUG . '">&nbsp;</span>';
        }
        elseif ($oBug->isContainer() !== true) {
            $aBlocks = $oBug->blocks();
            $oService = $oBug->getTicketService();
            $bHasContainer = false;
            foreach ($aBlocks as $iBlocks) {
                $oBlocks = $oService->getBugById($iBlocks);
                if ($oBlocks->isContainer() === true) {
                    $bHasContainer = true;
                    break;
                }
            }

            if ($bHasContainer === false) {
                $sClasses .= sprintf('&nbsp;<i class="%s" title="Feature with no container!">&nbsp;</i>', self::ICON_WARNING);
            }
        }

        return $sClasses;
    }
}
