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
namespace Flightzilla\Model\Timeline;

/**
 * A Date is one day of the timeline
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Date {

    /**
     * Amount of minutes, which a programmer is working each day
     *
     * @var int
     *
     * @TODO Config!
     */
    const AMOUNT = 6.0;

    /**
     * Amount of days, we'd like to plan in the future
     *
     * @var int
     *
     * @TODO Config!
     */
    const FUTURE = 10;

    /**
     * The day starts at
     *
     * @var string
     *
     * @TODO Config!
     */
    const START = '10:00';

    /**
     * The day ends at (exclude the break from the real-end!)
     *
     * @var string
     *
     * @TODO Config!
     */
    const END = '16:00';

    /**
     * List of workdays
     *
     * @var array
     */
    protected $_aWorkdays = array(
        '1' => true,
        '2' => true,
        '3' => true,
        '4' => true,
        '5' => true,
        '6' => false,
        '0' => false
    );

    /**
     * Check if a day is a holiday or a dayy of the weekend.
     *
     * @param $iTimestamp
     *
     * @return bool
     */
    public function isWorkFreeDay($iTimestamp) {

        $day = date('d', $iTimestamp);
        $month = date('m', $iTimestamp);
        $year = date('Y', $iTimestamp);

        // Parameter in richtiges Format bringen
        if (strlen($day) === 1) {
            $day = "0$day";
        }
        if (strlen($month) === 1) {
            $month = "0$month";
        }

        // Wochentag berechnen
        $date = getdate(mktime(0, 0, 0, $month, $day, $year));
        $weekday = $date['wday'];

        // Prüfen, ob Wochenende
        if ($weekday == 0 or $weekday == 6) {
            return true;
        }

        // Feste Feiertage werden nach dem Schema ddmm eingetragen
        $aHolidays[] = "0101"; // Neujahrstag
        $aHolidays[] = "0105"; // Tag der Arbeit
        $aHolidays[] = "0310"; // Tag der Deutschen Einheit
        $aHolidays[] = "3110"; // Reformationstag
        $aHolidays[] = "2512"; // Erster Weihnachtstag
        $aHolidays[] = "2612"; // Zweiter Weihnachtstag

        // Bewegliche Feiertage berechnen
        $days = 60 * 60 * 24;
        $easterSunday = easter_date($year);
        $aHolidays[] = date("dm", $easterSunday - 2 * $days); // Karfreitag
        $aHolidays[] = date("dm", $easterSunday + 1 * $days); // Ostermontag
        $aHolidays[] = date("dm", $easterSunday + 39 * $days); // Himmelfahrt
        $aHolidays[] = date("dm", $easterSunday + 50 * $days); // Pfingstmontag

        // get the wednesday before the 23rd of november
        $iDate = mktime(0, 0, 0, 11, 23, date('Y'));
        do {
            $iDate -= 86400;
            $date = getdate($iDate);
            $weekday = $date['wday'];

        }
        while ($weekday !== 3);

        $aHolidays[] = date('dm', $iDate); // Buß - und Bettag

        // Prüfen, ob Feiertag
        $code = $day . $month;
        return (in_array($code, $aHolidays) === true) ? true : false;
    }

    /**
     * Get timestamp of the next working day.
     *
     * @param $iTimestamp
     *
     * @return int
     */
    public function getNextWorkday($iTimestamp) {

        $d = 0;
        do {
            $iNextWorkday = strtotime('+' . $d . 'day', $iTimestamp);
            $bNonWorkingDay = $this->isWorkFreeDay($iNextWorkday);
            $d++;
        }
        while ($bNonWorkingDay);

        return $iNextWorkday;
    }

    /**
     * Check if the timestamp is greater than the current date + $iDays days
     *
     * @param  int $iTimestamp
     * @param  int $iDays
     *
     * @return boolean
     */
    public function isGreater($iTimestamp, $iDays) {
        return ($iTimestamp >= strtotime(sprintf('+%d days', $iDays)));
    }
}
