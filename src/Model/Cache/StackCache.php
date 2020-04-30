<?php


namespace Stacks\Model\Cache;

use Cake\Cache\Cache;
use Cake\Utility\Inflector;

class StackCache
{

    /**
     * @var string
     */
    protected $alias;

    /**
     * @var null|object Constants class
     */
    protected $con;


    public function __construct($alias, $constants = null)
    {
        $this->alias = $alias;
//        $this->con = $constants;

        if (is_null(Cache::getConfig($this->cacheName()))) {
            $groups = is_null($constants) ? [] : $this->con->$alias;
            Cache::setConfig($alias, [
                'className' => 'File',
                'path' => CACHE . 'stacks' . DS . Inflector::underscore($alias) . DS,
                'prefix' => 'stack_' . '_',
                'duration' => '+1 year',
                'serialize' => true,
//                'groups' => $groups,
            ]);
        }
    }

    /**
     * Read cache to see if the ID'd stack is present
     *
     * @param string $id Stack id will generate the cache data key
     * @return mixed The cached data, or FALSE
     */
    protected function read($id) {
        $result = Cache::read($this->cacheKey($id), $this->cacheName());
        return $result ?? false;
    }

    /**
     * Write a stack to the cache
     *
     * @param string $id
     * @param mixed $stack
     * @return bool True on successful cached, false on failure
     */
    protected function write($id, $stack) {
        return Cache::write($this->cacheKey($id), $stack, $this->cacheName());
    }

    public function delete($id)
    {
        return Cache::delete($this->cacheKey($id), $this->cacheName());
    }

    /**
     * Generate a cache key
     *
     * @param string $key An id
     * @return string The key
     */
    public function cacheKey($key) {
        return $key;
    }

    /**
     * Get the Cache config name for this configured cache
     *
     * @return string
     */
    public function cacheName() {
        return $this->alias;
    }

}
