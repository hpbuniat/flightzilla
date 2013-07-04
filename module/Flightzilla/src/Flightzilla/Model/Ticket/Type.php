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

namespace Flightzilla\Model\Ticket;

use Flightzilla\Model\Ticket\Type\Bug;

/**
 * Create a ticket as special type according to its keywords
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
abstract class Type {

    /**
     * The bug-types
     *
     * @var array
     */
    public static $aTypes = array(
        Bug::TYPE_STRING_BUG => Bug::TYPE_BUG,
        Bug::TYPE_STRING_PROJECT  => Bug::TYPE_PROJECT,
        Bug::TYPE_STRING_THEME  => Bug::TYPE_THEME,
        Bug::TYPE_STRING_CONCEPT  => Bug::TYPE_CONCEPT,
        Bug::TYPE_STRING_FEATURE  => Bug::TYPE_FEATURE,
    );

    /**
     * Create a ticket
     *
     * @param  \SimpleXMLElement $oXml
     *
     * @return \Flightzilla\Model\Ticket\Type\Bug|\Flightzilla\Model\Ticket\Type\Project|\Flightzilla\Model\Ticket\Type\Theme
     */
    public static function factory(\SimpleXMLElement $oXml) {
        $sType = self::getType($oXml);
        if ($sType === Bug::TYPE_PROJECT) {
            return new Type\Project($oXml);
        }
        elseif ($sType === Bug::TYPE_THEME) {
            return new Type\Theme($oXml);
        }

        return new Type\Bug($oXml);
    }

    /**
     * Get the type of a ticket
     *
     * @param  \SimpleXMLElement $oXml
     * @param  string $sTitle
     * @param  string $sKeywords
     *
     * @return string
     */
    public static function getType(\SimpleXMLElement $oXml, $sTitle = '', $sKeywords = '') {

        $sReturnType = false;

        $sTitle = (empty($sTitle) === true) ? (string) $oXml->short_desc : $sTitle;
        $sKeywords = (empty($sKeywords) === true) ? (string) $oXml->keywords : $sKeywords;

        foreach (self::$aTypes as $sSplitKeywords => $sType) {
            $aKeywords = explode(',', $sSplitKeywords);
            if (empty($aKeywords) !== true) {
                foreach ($aKeywords as $sKeyword) {
                    if (empty($sKeyword) !== true) {
                        if (stristr($sTitle, sprintf('%s:', $sKeyword)) !== false or stripos($sKeywords, $sKeyword) !== false) {
                            $sReturnType = $sType;
                            break 2;
                        }
                    }
                }
            }

            unset($aKeywords);
        }

        return $sReturnType;
    }
}
