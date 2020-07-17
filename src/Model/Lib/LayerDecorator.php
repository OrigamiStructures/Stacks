<?php /** @noinspection PhpUnused */



namespace Stacks\Model\Lib;


use Cake\ORM\Entity;
use Stacks\Constants\LayerCon;
use Stacks\Model\Entity\StackEntity;
use Stacks\Model\Traits\LayerElementAccessTrait;

class LayerDecorator
{

    /**
     * @var StackSet
     */
    protected $_component;

    /**
     * StackSetDecorator constructor.
     *
     * Send in the Layer or LayerDecorator to be decorated
     *
     * @param Layer|LayerDecorator $component
     */
    public function __construct($component)
    {
        $this->_component = $component;
    }

    //<editor-fold desc="PASS-THROUGH CALLS TO THE LAYER, 100% COVERAGE">

    /**
     * Gather the available data at this level and package the iterator
     *
     * @param $name string
     * @param null $className
     * @return LayerAccessProcessor
     */
    public function getLayer($name = null, $className = null)
    {
        return $this->_component->getLayer($name, $className);
    }

    /**
     * Get an new LayerAccessArgs instance
     * @return LayerAccessArgs
     */
    public function getArgObj()
    {
        return $this->_component->getArgObj();
    }

    public function getData()
    {
        return $this->_component->getData();
    }

    public function count(): int
    {
        return $this->_component->count();
    }

    /**
     * Get an array of the IDs of the stored entities
     *
     * @param null $layer
     * @return array
     */
    public function IDs($layer = null)
    {
        return $this->_component->IDs($layer);
    }

    /**
     * Get the result as an array of entities
     *
     * @return array
     */
    public function toArray()
    {
        return $this->_component->toArray();
    }

    /**
     * Get the result as Layer object
     *
     * @return Layer
     */
    public function toLayer()
    {
        return $this->_component->toLayer();
    }

    /**
     * Get an array of values
     *
     * @param $valueSource string|ValueSource
     * @return array
     */
    public function toValueList($valueSource = null)
    {
        return $this->_component->toValueList($valueSource);
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
        return $this->_component->toKeyValueList($keySource, $valueSource);
    }

    /**
     * Get a list of distinct values
     *
     * @param $valueSource string|ValueSource
     * @return array
     */
    public function toDistinctList($valueSource = null)
    {
        return $this->_component->toDistinctList($valueSource);
    }

    /**
     * Get the stored registry instance
     *
     * @return ValueSourceRegistry
     */
    public function getValueRegistry()
    {
        return $this->_component->getValueRegistry();
    }

    /**
     * Does the $property exist in this layer?
     *
     * This checks against visible properties, echos Entity::has()
     *
     * @param string $property
     * @return boolean
     */
    public function has($property)
    {
        return $this->_component->has($property);
    }

    /**
     * The type/name of this layer data
     *
     * @return string
     */
    public function layerName()
    {
        return $this->_component->layerName();
    }

    /**
     * The entity class name of the stored objects
     *
     * @param string $style 'bare' or 'namespaced'
     * @return string name of entity class stored in this layer
     */
    public function entityClass($style = 'bare')
    {
        return $this->_component->entityClass($style);
    }

    /**
     * Are are all the entities ( NOT dirty() )
     *
     * @return boolean
     * @todo This may not be appropriate. Make this immutable and handle edits in
     *      different structures external to this object? The entities can easily
     *      be taken out and put in a different object (except they are then
     *      references). But then again, when we emitNested arrays, they are references to
     *      these contained objects and so, might change
     *
     */
    public function isClean()
    {
        return $this->_component->isClean();
    }

    /**
     * Get the records with a matching foreign key value
     *
     * <code>
     * $pieces->linkedTo('format', 434)
     * </code>
     *
     * @param string $foreign The simple name of the associate (eg: artwork, format)
     * @param string $foreign_id The foreign key value to match
     * @return LayerAccessArgs
     */
    public function linkedTo($foreign, $foreign_id)
    {
        return $this->_component->linkedTo($foreign, $foreign_id);
    }
    //</editor-fold>

    //<editor-fold desc="LAYER ELEMENT ACCESS TRAIT COVERAGE">
    /**
     * Return the n-th stored element or element(ID)
     *
     * Data is stored in id-indexed arrays, but this method will let you
     * pluck the id's or n-th item out
     *
     * @param int $number Array index 0 through n or Id of element
     * @param boolean $byIndex LAYERACC_INDEX or LAYERACC_ID
     * @return Entity
     */
    public function element($key, $byIndex = LayerCon::LAYERACC_INDEX){
        return $this->_component->element($key, $byIndex);
    }

    /**
     * Get the last entity out of storage
     *
     * @return StackEntity|Entity|null
     */
    public function shift()
    {
        return $this->_component->shift();
    }

    /**
     * Get the first entity out of storage
     *
     * @return StackEntity|Entity|null
     */
    public function pop()
    {
        return $this->_component->pop();
    }
    //</editor-fold>

}
