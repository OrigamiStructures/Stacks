<?php
namespace Stacks\Model\Lib;

use Cake\Core\Configure;
use Cake\Core\ConventionsTrait;
use Cake\Datasource\EntityInterface;
use Cake\Error\Debugger;
use Cake\ORM\Entity;
use Cake\Utility\Inflector;
use Stacks\Lib\Traits\ErrorRegistryTrait;

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
     * @var Entity
     */
    private $entity;

    /**
	 * Construct the object
	 *
	 * @param string|Entity $entity plural, singular, lc, initial cap all ok
	 * @param string $source proper case, suffix '..' or '()' to force property or method
	 * @return boolean
	 */
	public function __construct($entity, $source) {
		$this->entity = $this->_getSample($entity);
		if ($this->_isEntity) {
			$this->_identify($this->entity, $source);
		}
		$this->_source = trim($source, '().');
//		return $this->isValid();
	}

	/**
	 * Report the name of the configured entity
	 *
	 * @return string
	 */
	public function entityName() {
		return namespaceSplit($this->_name)[1];
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
	 * @param EntityInterface $entity
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
			$namespace = Configure::read('test_mode')
                ? '\TestApp\Model\Entity\\'
                : '\App\Model\Entity\\';
			$className = $namespace .
                ucfirst($this->_singularName(Inflector::pluralize($entity)));
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
            $this->_isMethod = false;
            return;
        }
		if(stristr($source, '()')) {
			$this->_isMethod = TRUE;
            $this->_isProperty = false;
            return;
        }
		if (method_exists($this->_name, $source)) {
			$this->_isMethod = TRUE;
		}
		if (array_key_exists($source, $this->entity->getAccessible())) {
			$this->_isProperty = TRUE;
		}
		return;
	}

}
