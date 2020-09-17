<?php


namespace Stacks\Model\Lib;
use Cake\ORM\Entity;
use Stacks\Constants\LayerCon;
use Stacks\Model\Entity\StackEntity;
use Stacks\Model\Lib\LayerAccessArgs;
use Stacks\Model\Lib\LayerAccessProcessor;
use Stacks\Model\Lib\StackSet;

/**
 * Class StackSetDecorator
 *
 * Extend this class to add decorators to StackSets.
 *
 * @package App\Lib
 */
class StackSetDecorator
{
    /**
     * @var StackSet
     */
    protected $_component;

    /**
     * StackSetDecorator constructor.
     *
     * Send in the object to be decorated
     *
     * @param StackSet|StackSetDecorator $component
     */
    public function __construct($component)
    {
        $this->_component = $component;
    }

    /**
     * Get the count of stored entities
     *
     * @return int
     */
    public function count(): int
    {
        return $this->_component->count();
    }

    /**
     * Does the primary data or the named layer contain the id'd item?
     *
     * @param $id
     * @param string|null $layer
     * @return bool
     */
    public function hasId($id, $layer = null)
    {
        return $this->_component->hasId($id, $layer);
    }

    /**
     * Return the n-th stored element or element(ID)
     *
     * Data is stored in id-indexed arrays, but this method will let you
     * pluck the id's or n-th item out
     *
     * @param int $number Array index 0 through n or Id of element
     * @param boolean $byIndex LAYERACC_INDEX or LAYERACC_ID
     * @return StackEntity|Entity
     */
    public function element($key, $byIndex = LayerCon::LAYERACC_INDEX)
    {
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

    public function getTemplate()
    {
        return $this->_component->getTemplate();
    }

    /**
     * Gather the available data at this level and package the iterator
     *
     * @param $name string
     * @return LayerAccessProcessor
     */
    public function getLayer($name, $entityClass = null)
    {
        return $this->_component->getLayer($name, $entityClass);
    }

    /**
     * Get the list of layer in these stack entities
     *
     * @return array|string[]
     */
    public function getLayerList()
    {
        return $this->_component->getLayerList();
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

    /**
     * @return mixed
     */
    public function getRootLayerName()
    {
        return $this->_component->getRootLayerName();
    }

    /**
     * @return mixed
     */
    public function getPaginatedTableName()
    {
        return $this->_component->getPaginatedTableName();
    }

    /**
     * Get all the ids accross all the stored StackEntities or the Layer entities
     *
     * This is a collection-level method that matches the StackEntity's and Layer's
     * IDs() methods. These form a pass-through chain.
     *
     * Calling IDs() from this level will insure unique results if
     * Layer IDs are pulled.
     *
     * StackEntity IDs will be from the primary entity propery and will
     * be unique becuase the set structure insures it.
     *
     * @param string $layer
     * @return array
     */
    public function IDs($layer = null)
    {
        return $this->_component->IDs($layer);
    }

    public function linkedTo($foreign, $foreign_id, $linked = null)
    {
        return $this->_component->linkedTo($foreign, $foreign_id, $linked);
    }

    public function newStackSet($data)
    {
        return $this->_component->newStackSet($data);
    }

    /**
     * Return all StackEntities that contain a layer entity with id = $id
     *
     * @param string $layer
     * @param string $id
     * @return array
     * @todo This method seems confusing. Is it necessary?
     *
     */
    public function ownerOf($layer, $id)
    {
        return $this->_component->ownerOf($layer, $id);
    }

    /**
     * Get all StackEntities containing any of the layer elements in the set
     *
     * @param $layer string The layer to search in
     * @param $ids array The ids to search for
     */
    public function stacksContaining($layer, $ids)
    {
        return $this->_component->stacksContaining($layer, $ids);
    }

    /**
     * Add another entity to the StackSet
     *
     * @param string $id
     * @param StackEntity $stack
     */
    public function insertToStackSet($id, $stack)
    {
        $this->_component->insertToStackSet($id, $stack);
    }

    public function __debugInfo()
    {
        return $this->_component->__debugInfo();
    }


}
