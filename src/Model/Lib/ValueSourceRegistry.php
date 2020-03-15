<?php
namespace App\Model\Lib;

use Cake\Core\ObjectRegistry;
use App\Exception\MissingClassException;

/**
 * ValueSourceRegistry
 *
 * Provides registry services for LayerAccessArs. LAA keeps several
 * ValueSourceObject and may eventually require more. This registry
 * simplifies management of the various VSOs and will allow for
 * future expansion with minimal code change.
 *
 * @author dondrake
 */
class ValueSourceRegistry extends ObjectRegistry {

	/**
	 * A deep process of ValueSourceRegistry::load( )
	 *
	 * And load( ) is a deep process of LayerAccessArgs::buildAccessObject( )
	 *
	 * @param string $class The product of _resolveClassName($class)
	 * @param string $alias The name to file this object under
	 * @param array $config Guraranteed by LayerAccessArgs::buildAccessObject( )
	 * @return \App\Model\Lib\class
	 */
	protected function _create($class, $alias, $config) {
		return new $class($config['entity'], $config['node']);
	}

	/**
	 * $class is forced to 'ValueSource' for this class
	 *
	 * load( ) in concert with parent::load( )
	 * leads to this method and forces $class = 'ValueSource'
	 *
	 * @param string $class Always 'ValueSource'
	 * @return string
	 */
	protected function _resolveClassName($class) {
		return "\\App\\Model\\Lib\\$class";
	}

	/**
	 * Exception handling
	 *
	 * @param string $class
	 * @param string $plugin
	 * @throws MissingClassException
	 */
	protected function _throwMissingClassError($class, $plugin) {
		$msg = [];
		$msg[] = "The class $class ";
		$msg[] = !empty($plugin) ? "for plugin '$plugin' " : '';
		$msg[] = 'is missing. Needed by ValueSourceRegistry.';

		throw new MissingClassException(implode('', $msg));
	}

    /**
     * Loads/constructs an object instance.
	 *
	 * A process supporting LayerAccessArgs::buildAccessObject( )
	 * which guarantees $config keys that are eventually
	 * expected by ValueSourceRegistry::create( ) (called by parent::load( ))
	 *
	 * @param string $objectName The storage key
	 * @param array $config Caller guarantees content
     * @return mixed
     * @throws \Exception If the class cannot be found.
	 */
	public function load($objectName, $config = []) {
		 $config['className'] = 'ValueSource';
		 return parent::load($objectName, $config);
	 }

	 public function __debugInfo()
     {
         return parent::__debugInfo();
     }
}
