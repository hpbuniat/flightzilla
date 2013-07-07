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
namespace Flightzilla\Model\Timeline;

/**
 * A Date is one day of the timeline
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Date {

    /**
     * Amount of hours, which a programmer is working each day
     *
     * @var int
     *
     * @TODO Config!
     */
    const AMOUNT = 6.0;

    /**
     * Amount of hours which should be planne for a sprint per week
     *
     * @var int
     *
     * @TODO Config!
     */
    const WEEK = 30;

    /**
     * Amount of hours, we'd like to plan in the future
     *
     * @var int
     *
     * @TODO Config!
     */
    const FUTURE = 60;

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
     * Week aliases
     *
     * @var string
     */
    const WEEK_PREVIOUS = 'previous';
    const WEEK_CURRENT = 'current';
    const WEEK_NEXT = 'next';
    CONST WEEK_NEXT_BUT_ONE = 'next-but-one';

    /**
     * The weekly-sprint identifier
     *
     * @var array
     */
    protected $_aWeeks = array();

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
     * The holidays
     */
    protected $_aHolidays = array();

    /**
     * Create the date-helper and pre-calculate the holidays
     */
    public function __construct() {
        // Static holidays
        $iYear = date('Y');
        $iNextYear = $iYear + 1;
        $aHolidays['01.01.' . $iYear] = '01.01.' . $iYear; // Neujahrstag
        $aHolidays['01.01.' . $iNextYear] = '01.01.' . $iNextYear; // Neujahrstag
        $aHolidays['01.05.' . $iYear] = '01.05.' . $iYear; // Tag der Arbeit
        $aHolidays['01.05.' . $iNextYear] = '01.05.' . $iNextYear; // Tag der Arbeit
        $aHolidays['03.10.' . $iYear] = '03.10.' . $iYear; // Tag der Deutschen Einheit
        $aHolidays['03.10.' . $iNextYear] = '03.10.' . $iNextYear; // Tag der Deutschen Einheit
        $aHolidays['31.10.' . $iYear] = '31.10.' . $iYear; // Reformationstag
        $aHolidays['31.10.' . $iNextYear] = '31.10.' . $iNextYear; // Reformationstag
        $aHolidays['25.12.' . $iYear] = '25.12.' . $iYear; // Erster Weihnachtstag
        $aHolidays['25.12.' . $iNextYear] = '25.12.' . $iNextYear; // Erster Weihnachtstag
        $aHolidays['26.12.' . $iYear] = '26.12.' . $iYear; // Zweiter Weihnachtstag
        $aHolidays['26.12.' . $iNextYear] = '26.12.' . $iNextYear; // Zweiter Weihnachtstag

        // Bewegliche Feiertage berechnen
        $days = 60 * 60 * 24;
        $aYears = array(
            $iYear,
            $iNextYear
        );

        foreach ($aYears as $iTheYear) {
            $easterSunday = easter_date($iTheYear);
            $sKarFreitag = date("d.m.Y", $easterSunday - 2 * $days); // Karfreitag
            $aHolidays[$sKarFreitag] = $sKarFreitag; // Karfreitag
            $sOsterMontag = date("d.m.Y", $easterSunday + 1 * $days); // Ostermontag
            $aHolidays[$sOsterMontag] = $sOsterMontag; // Ostermontag
            $sHimmelfahrt = date("d.m.Y", $easterSunday + 39 * $days); // Himmelfahrt
            $aHolidays[$sHimmelfahrt] = $sHimmelfahrt; // Himmelfahrt
            $sPfingstMontag = date("d.m.Y", $easterSunday + 50 * $days); // Pfingstmontag
            $aHolidays[$sPfingstMontag] = $sPfingstMontag; // Pfingstmontag

            // get the wednesday before the 23rd of november
            $iDate = mktime(0, 0, 0, 11, 23, $iTheYear);
            do {
                $iDate -= 86400;
                $date = getdate($iDate);
                $weekday = $date['wday'];
            }
            while ($weekday !== 3);

            $sBettag = date('d.m.Y', $iDate);
            $aHolidays[$sBettag] = $sBettag; // Buß - und Bettag
        }
    }

    /**
     * Check if a day is a holiday or a day of the weekend.
     *
     * @param $iTimestamp
     *
     * @return bool
     */
    public function isWorkFreeDay($iTimestamp) {

        $sDate = date('d.m.Y', $iTimestamp);
        $aDate = explode('.', $sDate);
        $day = $aDate[0];
        $month = $aDate[1];
        $year = $aDate[2];
        unset($aDate);

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

        return ($weekday == 0 or $weekday == 6 or isset($this->_aHolidays[$sDate]) === true) ? true : false;
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

    /**
     * Get the week-identifiers
     *
     * @param  int $iSlice Slice some weeks from the result
     *
     * @return array
     */
    public function getWeeks($iSlice = 0) {
        if (empty($this->_aWeeks) === true) {
            $this->_aWeeks[self::WEEK_PREVIOUS] = array(
                'title' => date('Y/W', strtotime('previous week')),
                'tickets' => array()
            );
            $this->_aWeeks[self::WEEK_CURRENT] = array(
                'title' => date('Y/W', strtotime('this week')),
                'tickets' => array()
            );
            $this->_aWeeks[self::WEEK_NEXT] = array(
                'title' => date('Y/W', strtotime('next week')),
                'tickets' => array()
            );
            $this->_aWeeks[self::WEEK_NEXT_BUT_ONE] = array(
                'title' => date('Y/W', strtotime('next week', strtotime('next week'))),
                'tickets' => array()
            );
        }

        return array_slice($this->_aWeeks, $iSlice, null, true);
    }

    /**
     * Get a date (unix-timestamp) from a sprint-week
     *
     * @param  string $sWeek The week with notation yyyy/WW
     * @param  string $sDay The day of the week
     *
     * @return int
     */
    public function getDateFromWeek($sWeek, $sDay = 'thursday') {
        $aDate = explode('/', $sWeek);
        return strtotime(sprintf('%s-W%s %s %s', $aDate[0], $aDate[1], $sDay, self::END));
    }
}
