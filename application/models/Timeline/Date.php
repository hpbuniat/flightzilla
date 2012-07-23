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
 * A Date is one day of the timeline
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Model_Timeline_Date {

    /**
     * Amount of minutes, which a programmer is working each day
     *
     * @var int
     */
    const AMOUNT = 6.5;

    const START = '10:00';

    const END = '16:30';

    protected $_oDate;

    protected $_fLeft;

    protected $_aStack;


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
     * Create a date
     *
     * @param int $iTime
     */
    public function __construct($iTime) {
        $this->_oDate = new DateTime($iTime);
        $this->_fLeft = self::AMOUNT;
    }

    /**
     * Get the formatted date
     *
     * @return string
     */
    public function getFormatted() {
        return $this->_oDate->format('Y-m-d');
    }

    /**
     * Add a job for the day. If the return value is not 0, the remaining time has
     * to be added, to another date
     *
     * @param  float $fDuration
     * @param  sring $sDescription
     *
     * @return float Time left of this jobs
     */
    public function add($fDuration, $sDescription) {
        if ($fDuration <= $this->_fLeft) {
            $this->_aStack[$sDescription] = $fDuration;
            $this->_fLeft = $this->_fLeft - $fDuration;
        }
        else {
            $fPart = $fDuration - $this->_fLeft;

            $this->_aStack[$sDescription] = $this->_fLeft;
            $this->_fLeft = 0;
            return $fPart;
        }

        return 0;
    }
}