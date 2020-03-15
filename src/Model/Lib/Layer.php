<?php
namespace App\Model\Lib;

use App\Interfaces\LayerAccessInterface;
use App\Interfaces\LayerStructureInterface;
use App\Model\Lib\LayerAccessArgs;
use App\Model\Traits\LayerElementAccessTrait;
use Cake\Core\ConventionsTrait;
use Cake\ORM\Enitity;
use Cake\Collection\Collection;
use App\Exception\BadClassConfigurationException;
use \App\Interfaces\xxxLayerAccessInterface;
//use App\Model\Traits\LayerAccessTrait;
use App\Lib\Traits\ErrorRegistryTrait;

/**
 * StackLayer
 *
 * Streamline access to arrays of entities through a simple set of introspection
 * and retrieval methods.
 *
 * There are also some very basic filtering and sorting tools but it's not clear
 * how much use they would be. But it's also not clear whether they would be needed.
 * We may want to remove some of that code.
 *
 * @author Main
 */
class Layer implements LayerStructureInterface, LayerAccessInterface, \Countable {

    use ConventionsTrait;
	use ErrorRegistryTrait;
	use LayerElementAccessTrait;

    //<editor-fold desc="************** Properties **************">
    /**
     * The lower case, singular name of this layer (matches the entity type)
     *
     * @var string
     */
    protected $_layer;

    /**
     * Name of the stored entity class
     *
     * @var string
     */
    protected $_className;

    /**
     * The stored entities in this set
     *
     * @var array
     */
    protected $_data = [];

    /**
     * The properties that can be found on the entities
     *
     * @var array
     */
    protected $_entityProperties = [];
    //</editor-fold>

    /**
     * Populate the object
     *
     * This sets the layer name (lower class singular camelized),
     * name of the stored entity (eg: Format, Address, Piece),
     * and stores the provided entities indexed by their IDs.
     *
     * The actual entity class name will be used to set the two text
     * property values unless the array is empty. In that case, the name
     * must be provided on the second param or an exception is raised.
     *
     * @param array $entities
     * @param string $type Forced to camalized, ignored if entities are present
     * @throws BadClassConfigurationException
     */
    public function __construct(array $entities = [], $type = NULL) {
        try {

            $this->_initClassProperties($entities, $type);
            $this->_initEntitySet($entities);

        } catch (Exception $ex) {

            throw $ex;

        }
    }

    //<editor-fold desc="LayerStructureInterface implementation">
    /**
     * Gather the available data at this level and package the iterator
     *
     * @param $name string
     * @return LayerAccessProcessor
     */
    public function getLayer($name = null, $className = null)
    {
        $Iterator = new LayerAccessProcessor($this->layerName(), $this->entityClass());
        return  $Iterator->insert($this->_data);
    }

    /**
     * Get an new LayerAccessArgs instance
     * @return LayerAccessArgs
     */
    public function getArgObj()
    {
        return new LayerAccessArgs();
    }


    //</editor-fold>

    //<editor-fold desc="LayerAccessElementTrait abstract method implementations">
    public function getData()
    {
        return $this->_data;
    }

    public function count()
    {
        return count($this->_data);
    }

    /**
     * Get an array of the IDs of the stored entities
     *
     * @return array
     */
    public function IDs($layer = null) {
        return array_keys($this->getData());
    }
    //</editor-fold>

    //<editor-fold desc="LayerAccessInterface implementation">
    /**
     * Get the result as an array of entities
     *
     * @return array
     */
    public function toArray()
    {
        return $this->_data;
    }

    /**
     * Get the result as Layer object
     *
     * @return Layer
     */
    public function toLayer()
    {
        return $this;
    }

    /**
     * Get an array of values
     *
     * @param $valueSource string|ValueSource
     * @return array
     */
    public function toValueList($valueSource = null)
    {
        return $this->getLayer()->toValueList($valueSource);
    }

    /**
     * Get a key => value list
     *
     * @param $keySource string|ValueSource
     * @param $valueSource string|ValueSource
     * @return array
     */
    public function toKeyValueList($keySource = null, $valueSource = null)
    {
        return $this->getLayer()->toKeyValueList($keySource, $valueSource);
    }

    /**
     * Get a list of distinct values
     *
     * @param $valueSource string|ValueSource
     * @return array
     */
    public function toDistinctList($valueSource = null)
    {
        return $this->getLayer()->toDistinctList($valueSource);
    }

    /**
     * Get the stored registry instance
     *
     * @return ValueSourceRegistry
     */
    public function getValueRegistry()
    {
        return null;
    }

    //</editor-fold>

    //<editor-fold desc="************** Introspection **************">

    /**
     * Does the $property exist in this layer?
     *
     * This checks against visible properties, echos Entity::has()
     *
     * @param string $property
     * @return boolean
     */
    public function has($property) {
        return in_array($property, $this->_entityProperties);
    }

	/**
	 * The type/name of this layer data
	 *
	 * @return string
	 */
    public function layerName() {
        return $this->_layer;
    }

	/**
	 * The entity class name of the stored objects
	 *
	 * @param string $style 'bare' or 'namespaced'
	 * @return string name of entity class stored in this layer
	 */
    public function entityClass($style = 'bare') {
		if ($style === 'bare') {
			return $this->_className;
		} else {
			return 'App\\Model\\Entity\\'.$this->_className;
		}

    }

    /**
     * Are are all the entities ( NOT dirty() )
     *
     * @todo This may not be appropriate. Make this immutable and handle edits in
     *      different structures external to this object? The entities can easily
     *      be taken out and put in a different object (except they are then
     *      references). But then again, when we emit arrays, they are references to
     *      these contained objects and so, might change
     *
     * @return boolean
     */
    public function isClean() {
        $set = new Collection($this->_data);
        $result = $set->reduce(function ($accumulated, $entity) {
                return $accumulated && !($entity->isDirty());
             }, TRUE);
        return $result;
    }
    //</editor-fold>

    //<editor-fold desc="Associations">
    /**
     * Get the records with a matching foreign key value
     *
     * <code>
     * $pieces->linkedTo('format', 434)
     * </code>
     *
     * @param string $layer The simple name of the associate (eg: artwork, format)
     * @param string $id The foreign key value to match
     * @return LayerAccessArgs
     */
    public function linkedTo($foreign, $foreign_id) {
        $foreign_key = $this->_modelKey($foreign);

        return $this->getLayer()
            ->find()
            ->specifyFilter($foreign_key, $foreign_id);
    }
    //</editor-fold>

// <editor-fold defaultstate="collapsed" desc="************** Protected and Private **************">
    /**
     * Store all the provided entities indexed by id
     *
     * @param array $entities
     * @throws BadClassConfigurationException
     */
    private function _initEntitySet($entities) {
        foreach ($entities as $key => $entity) {
            if (!strpos(get_class($entity), $this->_className)) {
                $badClass = get_class($entity);
                $message = "All entities stored in a StackLayer must be of "
                    . "the same class. $this->_className was being used "
                    . "when $badClass was encountered.";
                throw new BadClassConfigurationException($message);
            }
            if (!isset($entity->id)) {
                $message = "StackLayer expects to find \$entity->id. This "
                    . "property was missing on array element $key. Did you "
						. "forget to name a layer when doing loadStack?";
                throw new BadClassConfigurationException($message);
            }
            $this->_data[$entity->id] = $entity;
        }
    }


    /**
     * Set the layer type and entity name for the object
     *
     * @param array $entities
     * @param string $type
     * @throws BadClassConfigurationException
     */
    private function _initClassProperties($entities, $type) {
        if (!empty($entities)) {
            $keys = array_keys($entities);
            $sampleData = $entities[$keys[0]];
            if (!is_object($sampleData) || !($sampleData instanceof \Cake\ORM\Entity)) {
                $badClass = get_class($sampleData);
                ;
                $message = 'StackLayer class can only accept objects that '
                    . 'extend Entity. The first object in the array is a $badClass '
                    . 'and does not extend Cake\ORM\Entity.';
                throw new BadClassConfigurationException($message);
            }
            $name = namespaceSplit(get_class($sampleData))[1];
        } else {
            if ($type === null) {
                $message = 'If no entities are provided, the name of the expected '
                    . 'entity type must be provided to the StackLayer class as the '
                    . 'second argument to __construct().';
                throw new BadClassConfigurationException($message);
            }
            $name = ucfirst($this->_singularName($type));
        }
        $this->_className = $name; //$this->_entityName($name);
        $this->_layer = strtolower($this->_camelize($this->_className));
        if (empty($sampleData)) {
            $class = "\App\Model\Entity\\$this->_className";
            $sampleData = new $class;
        }
        $this->_entityProperties = $sampleData->visibleProperties();
    }
// </editor-fold>

}
