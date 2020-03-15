<?php
namespace App\Model\Lib;

use Cake\Core\ConventionsTrait;
use Cake\ORM\Entity;
use Cake\Utility\Inflector;
use App\Lib\Traits\ErrorRegistryTrait;

/**
 * ValueSource
 * 
 * Validates and registers the name of a property or method that will 
 * be the source of an Entity value.
 *
 * @author dondrake
 */
class ValueSource {
	
	use ConventionsTrait;
	use ErrorRegistryTrait;
	
	/**
	 * Name of the property or method that provides the value
	 * @var string
	 */
	protected $_source = '';
	/**
	 * The namespaced name of the Entity 
	 * @var string
	 */
	protected $_name = '';
	/**
	 * Is this object constructed for a valid Entity class
	 * @var boolean
	 */
	protected $_isEntity = FALSE;
	/**
	 * Is the source a method
	 * @var boolean
	 */
	protected $_isMethod = FALSE;
	/**
	 * Is the source a property
	 * @var boolean
	 */
	protected $_isProperty = FALSE;
	
	/**
	 * Construct the object
	 * 
	 * @param string|Entity $entity plural, singular, lc, initial cap all ok
	 * @param string $source proper case, suffix '..' or '()' to force property or method
	 * @return boolean
	 */
	public function __construct($entity, $source) {
		$entity = $this->_getSample($entity);
		if ($this->_isEntity) {
			$this->_identify($entity, $source);
		}
		$this->_source = trim($source, '().');
		return $this->isValid();
	}
	
	/**
	 * Report the name of the configured entity
	 * 
	 * @return string
	 */
	public function entityName() {
		return lcfirst(namespaceSplit($this->_name));
	}
	
	/**
	 * Report the name of the node that will provide data
	 * 
	 * @return string
	 */
	public function sourceNode() {
		if ($this->_isMethod) {
			return "$this->_source()";
		}
		return $this->_source;
	}
	
	/**
	 * Can a value be returned from this object?
	 * 
	 * @return boolean
	 */
	public function isValid() {
		$ambiguous = $this->_isMethod && $this->_isProperty;
		return $this->_isEntity 
				&& !$ambiguous 
				&& ($this->_isMethod || $this->_isProperty);
	}
	
	/**
	 * Return the target value from this entity
	 * 
	 * @param Entity $entity
	 * @return mixed
	 */
	public function value(Entity $entity) {
		if (!$this->isValid()) {
			$ambiguous = $this->_isMethod && $this->_isProperty ? 'TRUE' : "FALSE";
			$method = $this->_isMethod ? 'TRUE' : "FALSE";
			$property = $this->_isProperty ? 'TRUE' : "FALSE";
			$this->registerError('If ' . get_class($entity) . 
					' is a generic entity, you\'ll have to bake the model '
					. 'and entity to use it, even if they are left empty.'
					. "Ambiguous: $ambiguous, Method: $method, "
					. "Property: $property, Entity: $this->_name, "
					. "SourceNode: $this->_source");
			return FALSE;
		}
		if ($this->_isMethod) {
			$result = $entity->{$this->_source}();
		} else {
			$result = $entity->{$this->_source};
		}
		return $result;
	}
	
	/**
	 * Verify and return an entity object
	 * 
	 * The name or an object might be sent. In either case, get an object 
	 * and if it is an Entity, set class properties and return it.
	 * 
	 * 'entity', 'Entity', 'dispositionsPieces', or 
	 * '\Name\Space\Entity' are valid strings
	 * 
	 * @param string|Entity $entity
	 * @return Entity
	 */
	private function _getSample($entity) {
		if (is_string($entity)) {
			$entity = namespaceSplit($entity);
			$entity = array_pop($entity);
			$className = "\\App\\Model\\Entity\\" . 
					$this->_singularName(Inflector::pluralize(ucfirst($entity)));
			$entity = new $className();
		}
		if (is_a($entity, '\Cake\ORM\Entity')) {
			$this->_isEntity = TRUE;
			$this->_name = get_class($entity);
		}
		return $entity;
		}
	
	/**
	 * Determine what the source points to on the Entity
	 * 
	 * property, method, neither, both
	 * can't detect bad property names
	 * 
	 * @param Entity $entity
	 * @param string $source
	 */
	private function _identify($entity, $source) {
		if(stristr($source, '..')) {
			$this->_isProperty = TRUE;
		} elseif(stristr($source, '()')) {
			$this->_isMethod = TRUE;
		} elseif (method_exists($this->_name, $source)) {
			$this->_isMethod = TRUE;
		} elseif (!empty($source)) {
			$this->_isProperty = TRUE;
		}
		return;
	}
	
}
