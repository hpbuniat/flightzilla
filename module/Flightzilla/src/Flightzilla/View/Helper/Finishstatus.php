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
 * View-Helper to highlight the estimated finish-date of a project
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
namespace Flightzilla\View\Helper;
use Zend\View\Helper\AbstractHelper;

class Finishstatus extends AbstractHelper {

    const ENDDATE = 'Estimation';
    const DEADLINE = 'Expectation';

    /**
     * Colorize the finish-date of a project
     *
     * @param  \Flightzilla\Model\Ticket\Type\Bug $oProject
     * @param  string $sWhich
     *
     * @return string
     */
    public function __invoke(\Flightzilla\Model\Ticket\Type\Bug $oProject, $sWhich = self::ENDDATE) {

        $sReturn = '';

        $iEndDate = ($sWhich === self::ENDDATE) ? $oProject->getEndDate() : strtotime($oProject->getDeadline());
        if (empty($iEndDate) !== true) {
            $iTime = time();

            $sClass = 'label-success';
            if ($iEndDate < $iTime) {
                $sClass = 'label-important';
            }
            elseif ($iEndDate < ($iTime + 172800)) {
                // within the next 2 days
                $sClass = 'label-warning';
            }
            elseif ($iEndDate < ($iTime + 604800)) {
                // within the next week
                $sClass = 'label-info';
            }

            $sDate = date('Y-m-d', $iEndDate);
            $iLeft = $oProject->getLeftTimeOfDependencies();
            if ($sWhich === self::ENDDATE and $iLeft > 0) {
                $iDays = ceil($iLeft / \Flightzilla\Model\Timeline\Date::AMOUNT);
                $oDate = new \Flightzilla\Model\Timeline\Date();
                if ($oDate->isGreater($iEndDate, $iDays) === false) {
                    $sWhich = sprintf('%s, adjusted by %d days (was %s)', $sWhich, $iDays, $sDate);
                    $iEndDate = strtotime(sprintf('+%d days', $iDays), $oDate->getNextWorkday(time()));
                    $sClass = 'label-important';
                }
            }

            $sDate = date('Y-m-d', $iEndDate);
            $sReturn = sprintf('<i class="icon-time"></i>&nbsp;<span data-title="%s" class="tipper label %s">%s</span>', $sWhich, $sClass, $sDate, $sDate);
        }
        else {
            $sReturn = sprintf('<span class="label label-important">%s missing!</span>', $sWhich);
        }

        return $sReturn;
    }
}
