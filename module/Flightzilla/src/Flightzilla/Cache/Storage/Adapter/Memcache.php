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
use stdClass;
use Traversable;
use Zend\Cache\Exception;
use Zend\Cache\Storage\AvailableSpaceCapableInterface;
use Zend\Cache\Storage\Capabilities;
use Zend\Cache\Storage\FlushableInterface;
use Zend\Cache\Storage\TotalSpaceCapableInterface;
use Zend\Cache\Storage\Adapter\AbstractAdapter;

/**
 * Memcache-Storage Adapter
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Memcache extends AbstractAdapter implements
    AvailableSpaceCapableInterface,
    FlushableInterface,
    TotalSpaceCapableInterface {

    /**
     * Major version of ext/memcached
     *
     * @var null|int
     */
    protected static $extMemcacheMajorVersion;

    /**
     * The memcached resource
     *
     * @var MemcacheResource
     */
    protected $MemcacheResource;

    /**
     * Constructor
     *
     * @param  null|array|Traversable|MemcacheOptions $options
     * @throws Exception\ExceptionInterface
     */
    public function __construct($options = null) {

        if (extension_loaded('memcache') !== true) {
            throw new Exception\ExtensionNotLoadedException('Need ext/memcache');
        }

        parent::__construct($options);
    }

    /**
     * Initialize the internal memcached resource
     *
     * @return MemcacheResource
     */
    protected function getMemcacheResource() {

        if ($this->MemcacheResource) {
            return $this->MemcacheResource;
        }

        $options = $this->getOptions();

        // use a configured resource or a new one
        $memcached = $options->getMemcacheResource() ? : new MemcacheResource();

        // init servers
        $servers = $options->getServers();
        if ($servers) {
            foreach ($servers as $aServer) {
                $memcached->addServer($aServer['host'], $aServer['port'], false, $aServer['weight']);
            }
        }

        // use the initialized resource
        $this->MemcacheResource = $memcached;

        return $this->MemcacheResource;
    }

    /* options */

    /**
     * Set options.
     *
     * @param  array|Traversable|MemcacheOptions $options
     * @return Memcache
     * @see    getOptions()
     */
    public function setOptions($options) {

        if (!$options instanceof MemcacheOptions) {
            $options = new MemcacheOptions($options);
        }

        return parent::setOptions($options);
    }

    /**
     * Get options.
     *
     * @return MemcacheOptions
     * @see setOptions()
     */
    public function getOptions() {

        if (!$this->options) {
            $this->setOptions(new MemcacheOptions());
        }
        return $this->options;
    }

    /* FlushableInterface */

    /**
     * Flush the whole storage
     *
     * @return boolean
     */
    public function flush() {

        $memc = $this->getMemcacheResource();
        if (!$memc->flush()) {
            throw $this->getExceptionByResultCode(false);
        }

        return true;
    }

    /* TotalSpaceCapableInterface */

    /**
     * Get total space in bytes
     *
     * @return int|float
     */
    public function getTotalSpace() {

        $memc = $this->getMemcacheResource();
        $stats = $memc->getExtendedStats();
        if ($stats === false) {
            throw new Exception\RuntimeException($stats);
        }

        $mem = array_pop($stats);
        return $mem['limit_maxbytes'];
    }

    /* AvailableSpaceCapableInterface */

    /**
     * Get available space in bytes
     *
     * @return int|float
     */
    public function getAvailableSpace() {

        $memc = $this->getMemcacheResource();
        $stats = $memc->getExtendedStats();
        if ($stats === false) {
            throw new Exception\RuntimeException($stats);
        }

        $mem = array_pop($stats);
        return $mem['limit_maxbytes'] - $mem['bytes'];
    }

    /* reading */

    /**
     * Internal method to get an item.
     *
     * @param  string  $normalizedKey
     * @param  boolean $success
     * @param  mixed   $casToken
     * @return mixed Data on success, null on failure
     * @throws Exception\ExceptionInterface
     */
    protected function internalGetItem(& $normalizedKey, & $success = null, & $casToken = null) {

        $memc = $this->getMemcacheResource();
        $result = $memc->get($normalizedKey);

        $success = true;
        if ($result === false) {
            $success = false;
        }

        return $result;
    }

    /**
     * Internal method to get multiple items.
     *
     * @param  array $normalizedKeys
     * @return array Associative array of keys and values
     * @throws Exception\ExceptionInterface
     */
    protected function internalGetItems(array & $normalizedKeys) {
        $result = array();
        foreach ($normalizedKeys as $sKey) {
            $result[$sKey] = $this->internalGetItem($sKey);
        }

        return $result;
    }

    /**
     * Internal method to test if an item exists.
     *
     * @param  string $normalizedKey
     * @return boolean
     * @throws Exception\ExceptionInterface
     */
    protected function internalHasItem(& $normalizedKey) {

        $memc = $this->getMemcacheResource();
        $value = $memc->get($normalizedKey);
        if ($value === false) {
            return false;
        }

        return true;
    }

    /**
     * Internal method to test multiple items.
     *
     * @param  array $normalizedKeys
     * @return array Array of found keys
     * @throws Exception\ExceptionInterface
     */
    protected function internalHasItems(array & $normalizedKeys) {

        $result = array();
        foreach ($normalizedKeys as $sKey) {
            $result[$sKey] = $this->internalGetItem($sKey);
        }

        return array_keys($result);
    }

    /**
     * Get metadata of multiple items
     *
     * @param  array $normalizedKeys
     * @return array Associative array of keys and metadata
     * @throws Exception\ExceptionInterface
     */
    protected function internalGetMetadatas(array & $normalizedKeys) {

        $result = array();
        foreach ($normalizedKeys as $sKey) {
            $result[$sKey] = $this->internalGetItem($sKey);
        }

        foreach ($result as & $value) {
            $value = array();
        }

        return $result;
    }

    /* writing */

    /**
     * Internal method to store an item.
     *
     * @param  string $normalizedKey
     * @param  mixed  $value
     * @return boolean
     * @throws Exception\ExceptionInterface
     */
    protected function internalSetItem(& $normalizedKey, & $value) {

        $memc = $this->getMemcacheResource();
        $expiration = $this->expirationTime();
        if (!$memc->set($normalizedKey, $value, MEMCACHE_COMPRESSED, $expiration)) {
            $this->getExceptionByResultCode();
        }

        return true;
    }

    /**
     * Internal method to store multiple items.
     *
     * @param  array $normalizedKeyValuePairs
     * @return array Array of not stored keys
     * @throws Exception\ExceptionInterface
     */
    protected function internalSetItems(array & $normalizedKeyValuePairs) {
        foreach ($normalizedKeyValuePairs as $sKey => $mValue) {
            $this->internalSetItem($sKey, $mValue);
        }

        return array();
    }

    /**
     * Add an item.
     *
     * @param  string $normalizedKey
     * @param  mixed  $value
     * @return boolean
     * @throws Exception\ExceptionInterface
     */
    protected function internalAddItem(& $normalizedKey, & $value) {

        $memc = $this->getMemcacheResource();
        $expiration = $this->expirationTime();
        if (!$memc->add($normalizedKey, $value, MEMCACHE_COMPRESSED, $expiration)) {
            $this->getExceptionByResultCode();
        }

        return true;
    }

    /**
     * Internal method to replace an existing item.
     *
     * @param  string $normalizedKey
     * @param  mixed  $value
     * @return boolean
     * @throws Exception\ExceptionInterface
     */
    protected function internalReplaceItem(& $normalizedKey, & $value) {

        $memc = $this->getMemcacheResource();
        $expiration = $this->expirationTime();
        if (!$memc->replace($normalizedKey, $value, MEMCACHE_COMPRESSED, $expiration)) {
              $this->getExceptionByResultCode();
        }

        return true;
    }

    /**
     * Internal method to remove an item.
     *
     * @param  string $normalizedKey
     * @return boolean
     * @throws Exception\ExceptionInterface
     */
    protected function internalRemoveItem(& $normalizedKey) {

        $memc = $this->getMemcacheResource();
        $result = $memc->delete($normalizedKey);

        if ($result === false) {
            $this->getExceptionByResultCode($result);
        }

        return true;
    }

    /* status */

    /**
     * Internal method to get capabilities of this adapter
     *
     * @return Capabilities
     */
    protected function internalGetCapabilities() {

        if ($this->capabilities === null) {
            $this->capabilityMarker = new stdClass();
            $this->capabilities = new Capabilities(
                $this,
                $this->capabilityMarker,
                array(
                     'supportedDatatypes' => array(
                         'NULL' => true,
                         'boolean' => true,
                         'integer' => true,
                         'double' => true,
                         'string' => true,
                         'array' => true,
                         'object' => 'object',
                         'resource' => false,
                     ),
                     'supportedMetadata' => array(),
                     'minTtl' => 1,
                     'maxTtl' => 0,
                     'staticTtl' => true,
                     'ttlPrecision' => 1,
                     'useRequestTime' => false,
                     'expiredRead' => false,
                     'maxKeyLength' => 255,
                     'namespaceIsPrefix' => false,
                )
            );
        }

        return $this->capabilities;
    }

    /* internal */

    /**
     * Get expiration time by ttl
     *
     * Some storage commands involve sending an expiration value (relative to
     * an item or to an operation requested by the client) to the server. In
     * all such cases, the actual value sent may either be Unix time (number of
     * seconds since January 1, 1970, as an integer), or a number of seconds
     * starting from current time. In the latter case, this number of seconds
     * may not exceed 60*60*24*30 (number of seconds in 30 days); if the
     * expiration value is larger than that, the server will consider it to be
     * real Unix time value rather than an offset from current time.
     *
     * @return int
     */
    protected function expirationTime() {

        $ttl = $this->getOptions()->getTtl();
        if ($ttl > 2592000) {
            return time() + $ttl;
        }

        return $ttl;
    }

    /**
     *
     * @param  boolean $code
     *
     * @return Exception\RuntimeException
     * @throws Exception\InvalidArgumentException On success code
     */
    protected function getExceptionByResultCode($code = false) {
        if ($code === false) {
            throw new Exception\RuntimeException('The operation failed');
        }

        return;
    }
}
