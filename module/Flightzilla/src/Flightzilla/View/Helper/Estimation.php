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
 * View-Helper to create the estimation-view
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
namespace Flightzilla\View\Helper;
use Zend\View\Helper\AbstractHelper;

class Estimation extends AbstractHelper {

    /**
     * The view-tye for the list-view
     *
     * @var string
     */
    const TICKET_LIST = 'list';

    /**
     * The view type for the board-view
     *
     * @var string
     */
    const TICKET_BOARD = 'board';

    /**
     * Determine the deadline-status of a bug
     *
     * @param  \Flightzilla\Model\Ticket\Type\Bug $oTicket
     * @param  string $sType
     *
     * @return string
     */
    public function __invoke(\Flightzilla\Model\Ticket\Type\Bug $oTicket, $sType = self::TICKET_LIST) {

        $bEstimated = $oTicket->isEstimated();
        $bOrga = ($oTicket->isOrga() or $oTicket->isContainer());
        $fActual = $oTicket->getActualTime();
        $fEstimated = $oTicket->getEstimation();
        $bOvertime = ($fActual >= (1.1 * $fEstimated));

        $sReturn = '';
        switch ($sType) {
            case self::TICKET_LIST:
                if ($bEstimated === true) {
                    $sReturn = sprintf('<span data-time="%01.2f" class="time %s">%01.2f (%01.2f)</span>', $fActual, ($bOvertime ? "red" : "green"), $fActual, $fEstimated);
                }
                elseif ($bOrga === true) {
                    $sSubstring = '';
                    if ($fActual > 0.00) {
                        $sSubstring = sprintf('(%01.2f)', $fActual);
                    }

                    $sReturn = sprintf('<span class="orga green">Orga %s</span>', $sSubstring);
                }
                else {
                    $sSubstring = '';
                    if ($fActual > 0.00) {
                        $sSubstring = sprintf('(%01.2f)', $fActual);
                    }

                    $sReturn = sprintf('<span class="unestimated red"><span class="ui-silk ui-silk-clock-red" title="Estimation missing">&nbsp;</span>%s</span>', $sSubstring);
                }

                break;

            case self::TICKET_BOARD:
                $bNew = ($oTicket->getStatus() === \Flightzilla\Model\Ticket\Type\Bug::STATUS_UNCONFIRMED or $oTicket->getStatus() === \Flightzilla\Model\Ticket\Type\Bug::STATUS_NEW);
                $sIcon = ($bNew === true) ? '<i class="glyphicon glyphicon-folder-open"></i>' : (($bOvertime === true) ? '<i class="glyphicon glyphicon-time"></i>' : '');

                if ($bEstimated === true) {
                    $sReturn = sprintf('<span class="pull-right name label %s">%s %s</span>', ($bOvertime ? "label-danger" : "label-success"), $sIcon, $oTicket->assignee_short);
                }
                else {
                    $sReturn = sprintf('<span class="pull-right name label %s">%s %s</span>', ($bOrga ? "label-success" : "label-warning"), $sIcon, $oTicket->assignee_short);
                }

                break;

            default:
                /* nop */
                break;
        }

        return $sReturn;
    }
}
