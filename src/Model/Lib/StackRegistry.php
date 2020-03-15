<?php

namespace App\Model\Lib;

use Cake\Core\ObjectRegistry;
use App\Exception\StackRegistryException;

/**
 * PersonCardRegistry
 * 
 * PersonCards are frequently used objects and appear as properties of 
 * several Stack types. This means that the same card may be needed in 
 * more than one object during a single Request/Response cycle. In such 
 * a case we need to insure all card data stays the same we will use this 
 * registry to maintain singletons and pass along references.
 * 
 * Registry operations are woven into the PersonCardsTable stack retrieval 
 * processes. This makes use of this registry (and caching) automatic. 
 * Just PersonCardsTable::find('stacksFor', [ ]) as you would for any 
 * request and you'll get a singleton based reference back (or a set of them). 
 * 
 * @author dondrake
 */
class StackRegistry {
	
	protected $_loaded = [];

	public function load($name, $stack = NULL) {
		if ($this->has($name) && !is_null($stack)) {
			throw new StackRegistryException('You must remove prior registry content first. '
					. 'Directly overwritting stored items is not allowed.');
		}
		if (!is_null($stack)) {
			$this->_loaded[$name] = $stack;
		}
		return $this->get($name);
	}
	
	public function get($name) {
		if (!$this->has($name)) {
			throw new StackRegistryException("The requested element \"$name\" is not "
					. 'in the registry.');
		}
		return $this->_loaded[$name];
	}

	public function has($name) {
		return isset($this->_loaded[$name]);
	}
	
	public function remove($name) {
		if ($this->has($name)) {
			unset($this->_loaded[$name]);
		}
	}
	
	public function reset() {
		$this->_loaded = [];
	}
	
}
