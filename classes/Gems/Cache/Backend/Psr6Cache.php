<?php

/**
 * A Zend Cache 1 backend wrapper for PSR-6 compatible Cache libraries like Symfony cache 3.3+
 */

namespace Gems\Cache\Backend;

use Gems\Cache\Backend;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

class Psr6Cache extends Backend implements \Zend_Cache_Backend_ExtendedInterface
{
    protected $cache;

    protected $reservedCharacters = [
        '{',
        '}',
        '(',
        ')',
        '/',
        '\\',
        '@',
        ':',
    ];

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * PSR-6 has reserved the characters {}()/\@:
     * Zend uses these characters by default. We will replace them with an underscore
     *
     * @param $id
     * @return string|string[]
     */
    protected function filterReservedCharacters($id)
    {
        return str_replace($this->reservedCharacters, '_', $id);
    }

    /**
     * Get the cache storage adapter
     *
     * @return CacheItemPoolInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Test if a cache is available for the given id and (if yes) return it (false else)
     *
     * Note : return value is always "string" (unserialization is done by the core not by the backend)
     *
     * @param  string  $id                     Cache id
     * @param  boolean $doNotTestCacheValidity If set to true, the cache validity won't be tested
     * @return string|false cached datas
     */
    public function load($id, $doNotTestCacheValidity = false)
    {
        $id = $this->filterReservedCharacters($id);
        $item = $this->cache->getItem($id);
        return $item->get();
    }

    /**
     * Test if a cache is available or not (for the given id)
     *
     * @param  string $id cache id
     * @return mixed|false (a cache is not available) or "last modified" timestamp (int) of the available cache record
     */
    public function test($id)
    {
        $id = $this->filterReservedCharacters($id);
        $this->cache->hasItem($id);
    }

    /**
     * Save some string datas into a cache record
     *
     * Note : $data is always "string" (serialization is done by the
     * core not by the backend)
     *
     * @param  string $data            Datas to cache
     * @param  string $id              Cache id
     * @param  array $tags             Array of strings, the cache record will be tagged by each string entry
     * @param  int   $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
     * @return boolean true if no problem
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        $id = $this->filterReservedCharacters($id);
        $item = $this->cache->getItem($id);
        $item->set($data);
        if ($specificLifetime !== false) {
            $item->expiresAfter($specificLifetime);
        }

        if ($this->isTagAware()) {
            $item->tag($tags);
        } else {
            $this->_log('Tag support is not enabled in this PSR-6 backend');
        }

        return $this->cache->save($item);
    }

    /**
     * Remove a cache record
     *
     * @param  string $id Cache id
     * @return boolean True if no problem
     */
    public function remove($id)
    {
        $id = $this->filterReservedCharacters($id);
        $this->cache->deleteItem($id);
    }

    /**
     * Clean some cache records
     *
     * Available modes are :
     * Zend_Cache::CLEANING_MODE_ALL (default)    => remove all cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_OLD              => remove too old cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_MATCHING_TAG     => remove cache entries matching all given tags
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG => remove cache entries not {matching one of the given tags}
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG => remove cache entries matching any given tags
     *                                               ($tags can be an array of strings or a single string)
     *
     * @param  string $mode Clean mode
     * @param  array  $tags Array of tags
     * @return boolean true if no problem
     */
    public function clean($mode = \Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        switch ($mode) {
            case \Zend_Cache::CLEANING_MODE_ALL:
                $this->cache->clear();
                return true;
                break;
            case \Zend_Cache::CLEANING_MODE_OLD:
                throw new \Exception("CLEANING_MODE_OLD is unsupported by the PSR-6 backends.");
                break;
            case \Zend_Cache::CLEANING_MODE_MATCHING_TAG:
            case \Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG:
            case \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG:

                if (!$this->isTagAware()) {
                    throw new \Exception("Tags are unsupported by this PSR-6 backend.");
                }

                $this->cache->invalidateTags($tags);
                break;
            default:
                \Zend_Cache::throwException('Invalid mode for clean() method');
                break;
        }
    }

    /**
     * Return an array of stored cache ids
     *
     * @return array array of stored cache ids (string)
     */
    public function getIds()
    {
        throw new \Exception('Get list of set ids is not supported in PSR-6 backends');
    }

    /**
     * Return an array of stored tags
     *
     * @return array array of stored tags (string)
     */
    public function getTags()
    {
        throw new \Exception('Get list of set tags is not supported in PSR-6 backends');
    }

    /**
     * Return an array of stored cache ids which match given tags
     *
     * In case of multiple tags, a logical AND is made between tags
     *
     * @param array $tags array of tags
     * @return array array of matching cache ids (string)
     */
    public function getIdsMatchingTags($tags = array())
    {
        throw new \Exception('Get list of set Ids matching a tag is not supported in PSR-6 backends');
    }

    /**
     * Return an array of stored cache ids which don't match given tags
     *
     * In case of multiple tags, a logical OR is made between tags
     *
     * @param array $tags array of tags
     * @return array array of not matching cache ids (string)
     */
    public function getIdsNotMatchingTags($tags = array())
    {
        throw new \Exception('Get list of set Ids not matching a tag is not supported in PSR-6 backends');
    }

    /**
     * Return an array of stored cache ids which match any given tags
     *
     * In case of multiple tags, a logical AND is made between tags
     *
     * @param array $tags array of tags
     * @return array array of any matching cache ids (string)
     */
    public function getIdsMatchingAnyTags($tags = array())
    {
        throw new \Exception('Get list of set Ids matching any tag is not supported in PSR-6 backends');
    }

    /**
     * Return the filling percentage of the backend storage
     *
     * @return int integer between 0 and 100
     */
    public function getFillingPercentage()
    {
        throw new \Exception('Get filling percentage is not supported in PSR-6 backends');
    }

    /**
     * Return an array of metadatas for the given cache id
     *
     * The array must include these keys :
     * - expire : the expire timestamp
     * - tags : a string array of tags
     * - mtime : timestamp of last modification time
     *
     * @param string $id cache id
     * @return array array of metadatas (false if the cache id is not found)
     */
    public function getMetadatas($id)
    {
        throw new \Exception('Get metadatas is not supported in PSR-6 backends');
    }

    /**
     * Give (if possible) an extra lifetime to the given cache id
     *
     * @param string $id cache id
     * @param int $extraLifetime
     * @return boolean true if ok
     */
    public function touch($id, $extraLifetime)
    {
        $id = $this->filterReservedCharacters($id);
        $item = $this->cache->getItem($id);
        $item->expiresAfter($extraLifetime);
        $this->cache->save($item);
    }

    /**
     * Return an associative array of capabilities (booleans) of the backend
     *
     * The array must include these keys :
     * - automatic_cleaning (is automating cleaning necessary)
     * - tags (are tags supported)
     * - expired_read (is it possible to read expired cache records
     *                 (for doNotTestCacheValidity option for example))
     * - priority does the backend deal with priority when saving
     * - infinite_lifetime (is infinite lifetime can work with this backend)
     * - get_list (is it possible to get the list of cache ids and the complete list of tags)
     *
     * @return array associative of with capabilities
     */
    public function getCapabilities()
    {
        $capabilities = [
            'automatic_cleaning' => true,
            'tags' => false,
            'expired_read' => false,
            'priority' => false,
            'infinite_lifetime' => false,
            'get_list' => false,
        ];

        if ($this->isTagAware()) {
            $capabilities['tags'] = true;
        }

        return $capabilities;
    }

    /**
     * @return bool Can the caching adapter use Tags?
     */
    public function isTagAware()
    {
        if ($this->cache instanceof TagAwareAdapterInterface) {
            return true;
        }

        return false;
    }
}
