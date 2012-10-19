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
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Cache
 */

namespace Flightzilla\Cache\Storage\Adapter;

use Memcache as MemcacheResource;
use Zend\Cache\Storage\Adapter\AdapterOptions;
use Zend\Cache\Exception\InvalidArgumentException;

/**
 * Memcache-Storage Options
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class MemcacheOptions extends AdapterOptions {

    /**
     * A memcached resource to share
     *
     * @var null|MemcacheResource
     */
    protected $MemcacheResource;

    /**
     * List of memcached servers to add on initialize
     *
     * @var string
     */
    protected $servers = array(
        array(
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 100,
        ),
    );

    /**
     * A memcached resource to share
     *
     * @param null|MemcacheResource $MemcacheResource
     * @return MemcacheOptions
     */
    public function setMemcacheResource(MemcacheResource $MemcacheResource = null) {

        if ($this->MemcacheResource !== $MemcacheResource) {
            $this->triggerOptionEvent('memcached_resource', $MemcacheResource);
            $this->MemcacheResource = $MemcacheResource;
        }

        return $this;
    }

    /**
     * Get memcached resource to share
     *
     * @return null|MemcacheResource
     */
    public function getMemcacheResource() {

        return $this->MemcacheResource;
    }

    /**
     * Add a server to the list
     *
     * @param  string $host
     * @param  int $port
     * @param  int $weight
     * @return MemcacheOptions
     */
    public function addServer($host, $port = 11211, $weight = 0) {

        $new = array(
            'host' => $host,
            'port' => $port,
            'weight' => $weight
        );

        foreach ($this->servers as $server) {
            $diff = array_diff($new, $server);
            if (empty($diff)) {
                // Done -- server is already present
                return $this;
            }
        }

        $this->servers[] = $new;
        return $this;
    }

    /**
     * Set a list of memcached servers to add on initialize
     *
     * @param string|array $servers list of servers
     * @return MemcacheOptions
     * @throws InvalidArgumentException
     */
    public function setServers($servers) {

        if (!is_array($servers)) {
            return $this->setServers(explode(',', $servers));
        }

        $this->servers = array();
        foreach ($servers as $server) {
            // default values
            $host = null;
            $port = 11211;
            $weight = 1;

            if (!is_array($server) && !is_string($server)) {
                throw new InvalidArgumentException('Invalid server specification provided; must be an array or string');
            }

            // parse a single server from an array
            if (is_array($server)) {
                if (!isset($server[0]) && !isset($server['host'])) {
                    throw new InvalidArgumentException("Invalid list of servers given");
                }

                // array(array(<host>[, <port>[, <weight>]])[, ...])
                if (isset($server[0])) {
                    $host = (string) $server[0];
                    $port = isset($server[1]) ? (int) $server[1] : $port;
                    $weight = isset($server[2]) ? (int) $server[2] : $weight;
                }

                // array(array('host' => <host>[, 'port' => <port>[, 'weight' => <weight>]])[, ...])
                if (!isset($server[0]) && isset($server['host'])) {
                    $host = (string) $server['host'];
                    $port = isset($server['port']) ? (int) $server['port'] : $port;
                    $weight = isset($server['weight']) ? (int) $server['weight'] : $weight;
                }
            }

            // parse a single server from a string
            if (!is_array($server)) {
                $server = trim($server);
                if (strpos($server, '://') === false) {
                    $server = 'tcp://' . $server;
                }

                $server = parse_url($server);
                if (!$server) {
                    throw new InvalidArgumentException("Invalid list of servers given");
                }

                $host = $server['host'];
                $port = isset($server['port']) ? (int) $server['port'] : $port;

                if (isset($server['query'])) {
                    $query = null;
                    parse_str($server['query'], $query);
                    if (isset($query['weight'])) {
                        $weight = (int) $query['weight'];
                    }
                }
            }

            if (!$host) {
                throw new InvalidArgumentException('The list of servers must contain a host value.');
            }

            $this->addServer($host, $port, $weight);
        }

        return $this;
    }

    /**
     * Get Servers
     *
     * @return array
     */
    public function getServers() {

        return $this->servers;
    }
}
