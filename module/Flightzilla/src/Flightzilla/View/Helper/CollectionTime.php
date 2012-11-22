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
 * View-Helper to get the summarized times of a ticket-collection
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
namespace Flightzilla\View\Helper;
use Zend\View\Helper\AbstractHelper;

class CollectionTime extends AbstractHelper {

    /**
     * Get the summarized times
     *
     * @param  array $aTickets
     * @param  boolean $bProject
     *
     * @return string
     */
    public function __invoke(array $aTickets, $bProject = false) {
        $aTimes = array(
            'spent' => 0,
            'esti' => 0,
            'left' => 0
        );

        foreach($aTickets as $oTicket) {
            if ($oTicket->isEstimated()) {
                $fSpent = (float) $oTicket->actual_time;
                $fEsti = (float) $oTicket->estimated_time;
                $fLeft = ($fEsti > $fSpent and $oTicket->isStatusAtLeast(\Flightzilla\Model\Ticket\Type\Bug::STATUS_RESOLVED) !== true) ? ($fEsti - $fSpent) : 0;
                $aTimes['spent'] += $fSpent;
                $aTimes['esti'] += $fEsti;
                $aTimes['left'] += $fLeft;
            }
        }

        $aTimes['days'] = round($aTimes['left'] / \Flightzilla\Model\Timeline\Date::AMOUNT, 1);
        $aTimes['future'] = round(($aTimes['left'] / (\Flightzilla\Model\Timeline\Date::FUTURE * \Flightzilla\Model\Timeline\Date::AMOUNT)) * 100, 1);
        $aTimes['spent_days'] = round($aTimes['spent'] / \Flightzilla\Model\Timeline\Date::AMOUNT, 1);
        $aTimes['esti_days'] = round($aTimes['esti'] / \Flightzilla\Model\Timeline\Date::AMOUNT, 1);

        $aTimes['percent'] = 0;
        if ($aTimes['esti'] > 0) {
            $aTimes['percent'] = round((1 - ($aTimes['left'] / $aTimes['esti'])) * 100, 1);
        }

        if ($bProject === true) {
            $aTimes['color'] = 'danger';
            if ($aTimes['percent'] > 80) {
                $aTimes['color'] = 'success';
            }
            elseif ($aTimes['percent'] > 50) {
                $aTimes['color'] = 'info';
            }
            elseif ($aTimes['percent'] > 20) {
                $aTimes['color'] = 'warning';
            }
        }
        else {
            $aTimes['color'] = 'success';
            if ($aTimes['future'] < 20) {
                $aTimes['color'] = 'danger';
            }
            elseif ($aTimes['future'] < 50) {
                $aTimes['color'] = 'warning';
            }
            elseif ($aTimes['future'] < 80) {
                $aTimes['color'] = 'info';
            }
        }

        return $aTimes;
    }
}
