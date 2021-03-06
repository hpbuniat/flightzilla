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
namespace Flightzilla\Model\Resource\Human;

/**
 * The work-times of a resource
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Timecard {

    /**
     * Dates with worked hours
     *
     * @var array
     */
    protected $_aDates = array();

    /**
     * The resource of this timecard
     *
     * @var string
     */
    protected $_sResource;

    /**
     * Set the resource
     *
     * @param  string $sResource
     *
     * @return $this
     */
    public function setResource($sResource) {
        $this->_sResource = (string) $sResource;
        return $this;
    }

    /**
     * Handle a ticket
     *
     * @param  \Flightzilla\Model\Ticket\Type\Bug $oTicket
     *
     * @return $this
     */
    public function handle(\Flightzilla\Model\Ticket\Type\Bug $oTicket) {
        $aHours = $oTicket->getWorkedHours();

        foreach ($aHours as $aTime) {
            if ($aTime['user_mail'] === $this->_sResource) {
                if (isset($this->_aDates[$aTime['date']]) !== true) {
                    $this->_aDates[$aTime['date']] = array();
                }

                $this->_aDates[$aTime['date']][] = array(
                    'time' => $aTime['duration'],
                    'start' => ($aTime['datetime'] - (3600 * $aTime['duration'])),
                    'end' => $aTime['datetime'],
                    'ticket' => $oTicket
                );
            }
        }

        return $this;
    }

    /**
     * Get the times
     *
     * @return array
     */
    public function getTimes() {
        ksort($this->_aDates);
        foreach ($this->_aDates as $sDate => $aDate) {
            ksort($this->_aDates[$sDate]);
        }

        return $this->_aDates;
    }

    /**
     * Get the times for a gantt-graph
     *
     * @param  int $iDays
     *
     * @return array
     */
    public function getTimesAsGantt($iDays) {
        $aTimes = $this->getTimes();
        $iCompare = strtotime(sprintf('-%d days', $iDays));

        $aGantt = array();
        foreach ($this->_aDates as $sDate => $aDate) {
            if (strtotime($sDate) >= $iCompare) {
                $aGanttAdd = array(
                    'name' => (count($aGantt) === 0 ? $this->_sResource : ''),
                    'values' => array()
                );

                $fHours = 0;
                $iStart = strtotime(sprintf('%s %s', $sDate, \Flightzilla\Model\Timeline\Date::START));
                foreach ($aDate as $aTime) {
                    $fHours += $aTime['time'];
                    $iEnd = $iStart + ($aTime['time'] * 3600);
                    $aGanttAdd['values'][] = array(
                        'from'        => '/Date(' . $iStart * 1000 . ')/',
                        'to'          => '/Date(' . $iEnd * 1000 . ')/',
                        'label'       => $aTime['ticket']->title(),
                        'desc'        => '<b>' . $aTime['ticket']->title() . '</b><br />'
                            . '<b>Assignee:</b> ' . (string) $aTime['ticket']->getResource() . '<br />'
                            . '<b>Start:</b> ' . date('d.m.Y H:i', $aTime['start']) . '<br />'
                            . '<b>Ende:</b> ' . date('d.m.Y H:i', $aTime['end']) . '<br />'
                            . substr((string) $aTime['ticket']->long_desc->thetext, 0, 120)
                    );

                    $iStart = $iEnd + 300;
                }

                $aGanttAdd['desc'] = sprintf('%s (%.2fh)', $sDate, $fHours);
                $aGantt[] = $aGanttAdd;
            }
        }

        return $aGantt;
    }

    /**
     * Get the times for a stack
     *
     * @param  int $iDays
     *
     * @return array
     */
    public function getTimesAsStack($iDays) {
        $aTimes = $this->getTimes();
        $iCompare = strtotime(sprintf('-%d days', $iDays));

        $aStack = array();
        foreach ($this->_aDates as $sDate => $aDate) {
            if (strtotime($sDate) >= $iCompare) {
                $aAdd = array(
                    'time' => 0,
                    'tickets' => array()
                );

                $fHours = 0;
                foreach ($aDate as $aTime) {
                    $sTicket = $aTime['ticket']->id();
                    if (empty($aAdd['tickets'][$sTicket]) === true) {
                        $aAdd['tickets'][$sTicket] = array(
                            'title' => $aTime['ticket']->title(),
                            'time' => 0
                        );
                    }

                    $aAdd['tickets'][$sTicket]['time'] += $aTime['time'];
                    $fHours += $aTime['time'];
                }

                $aAdd['time'] = $fHours;
                $aStack[$sDate] = $aAdd;
            }
        }

        return $aStack;
    }
}
