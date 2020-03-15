<?php

namespace App\Model\Entity;

use App\Exception\UnknownLayerException;
use App\Interfaces\LayerStructureInterface;
use App\Model\Lib\LayerAccessProcessor;
use Cake\ORM\Entity;
use App\Model\Lib\Layer;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use App\Interfaces\xxxLayerAccessInterface;
use App\Model\Lib\LayerAccessArgs;
use App\Exception\BadClassConfigurationException;
use Cake\Utility\Text;
use http\Exception\BadMethodCallException;
use http\Exception\InvalidArgumentException;

/**
 * Stacks
 *
 * Tools to manage multiple Layer object properties in a containing object
 *
 * Provide reporting tools to see what records are stored in the contained
 * entity stacks.
 *
 * Provide accessor tools to extract contained objects with explicit looping
 *
 * @author Main
 */
class StackEntity extends Entity implements LayerStructureInterface
{

    /**
     * Name of the tip-of-the-iceberg entity for this stack
     *
     * The value migrates forward from the concrete stackTable
     * during creation and population of the entity and its values
     *
     * @see App\Model\Table\StacksTable::newVersionMarshalStack()
     *
     * @var string
     */
    protected $rootName = FALSE;

    /**
     * The displayField source for the root entity
     *
     * displayField() is a Table concept and is used for several find()
     * variants. Since stackEntities mimic some of these features, they
     * need to include a displayField() equivalent.
     *
     * StackTable migrates this table-based value into to stackEntities
     * where it takes on the additonal ability to be the name of a method
     * that has no arguemnts (eg: name( ) ).
     *
     * [1] The value wil be the moved forward from one of two sources:
     *        displayField() of the root layers underlying table
     *        $rootDisplaySource of the concrete stackTable for this entity
     *
     * @todo Make [1] a true statement
     *
     * @see App\Model\Table\StacksTable::newVersionMarshalStack()
     *
     * @var string
     */
    public $rootDisplaySource = FALSE;

    /**
     * names of the layers and thier Entity types
     *
     * eg:
     * [
     *      'identity' => 'Member',
     *      'artwork' => 'Artwork'
     * ]
     *
     * @var array
     */
    public $schema;

    //<editor-fold desc="LayerStructureInterface Realization">
    /**
     * Gather the available data at this level and package the iterator
     *
     * @param $name string
     * @return LayerAccessProcessor
     * @throws \BadMethodCallException
     */
    public function getLayer($name, $className = null)
    {
        if (is_null($this->$name)) {
            $msg = "The layer '$name' is not the name of a layer in " . get_class($this);
            throw new \BadMethodCallException($msg);
        }

        $className = $className ?? $this->$name->entityClass();

        $Iterator = new LayerAccessProcessor($name, $className);
        if (is_a($this->$name, '\App\Model\Lib\Layer')) {
            $result = $this->$name;
        } else {
            $result = [];
        }
        return $Iterator->insert($result);
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


    //<editor-fold desc="Introspection">

    /**
     * Get this primary id or the IDs of all the entities in a layer
     *
     * @param string $layer
     * @return array
     */
    public function IDs($layer = null)
    {
        if(is_null($layer)) {
            $result = $this->rootID(LAYERACC_WRAP);
        } else {
            $result = $this->getLayer($layer)->toDistinctList('id');
        }
        return $result;
    }

    /**
     * Adds Layer property empty checks to other native checks
     *
     * {@inheritdoc}
     *
     * @param string $property The property to check.
     * @return bool
     */
    public function isEmpty($property = null)
    {
        if (is_null($property)) {
            $property = $this->rootLayerName();
        }
        $value = $this->get($property);
        if (is_object($value)
            && $value instanceof \App\Model\Lib\Layer
            && $value->count() === 0
        ) {
            return true;
        }
        return parent::isEmpty($property);
    }

    /**
     * Is the id a member of the set
     *
     * @param string $id
     * @return boolean
     * @todo Overlap with Entity has() method. Resolve our name strategy
     *
     */
    public function exists($layer, $id)
    {
        $property = $this->get($layer);
        if ($property) {
            return $property->hasId($id);
        }
        return FALSE;
    }

    /**
     * Get the count of entities in a layer
     *
     * @todo There is still the possiblity of having empty properties
     *      hold empty Layers so they count and act normally
     *
     * @param string $layer
     * @return int
     */
    public function count($layer)
    {
        $property = $this->get($layer);
        if (is_countable($property)) {
            return $property->count();
        }
        return 0;
    }

    public function hasNo($layer)
    {
        return $this->count($layer) === 0;
    }

    public function hasLayer($layer)
    {
        return $this->count($layer) > 0;
    }
    //</editor-fold>

    //<editor-fold desc="Root Layer Introspection and Access">

    /**
     * Return the owner id of the primary entity
     *
     * @return string
     */
    public function dataOwnerId()
    {
        return $this->rootElement()->user_id;
    }

    /**
     * Get the card identity entity
     *
     * Optionally get the entity as an array element
     *
     * @param boolean $unwrap
     * @return entity|array
     */
    public function rootElement($unwrap = LAYERACC_UNWRAP)
    {
        $result = $this->{$this->rootLayerName()}->toArray();
        return $this->_resolveWrapper($result, $unwrap);
    }

    public function setRoot($layer)
    {
        $this->set('rootName', $layer);
        return $this;
    }

    public function setRootDisplaySource($source)
    {
        $this->set('rootDisplaySource', $source);
        return $this;
    }

    /**
     * Get id of the card cap entity
     *
     * Optionally get the id as an array element
     *
     * @param boolean $unwrap
     * @return string|array
     */
    public function rootID($unwrap = LAYERACC_UNWRAP)
    {
        $result = $this->{$this->rootLayerName()}->IDs();
        return $this->_resolveWrapper($result, $unwrap);
    }

    /**
     * Get displayValue for the card's cap entity
     *
     * Optionally get the name as an array element
     *
     * @param boolean $unwrap
     * @return string|array
     */
    public function rootDisplayValue($unwrap = LAYERACC_UNWRAP)
    {
        /* @var Layer $rootLayer */
        $rootLayer = layer($this->rootElement(LAYERACC_WRAP));
        $title = $rootLayer->toValueList($this->rootDisplaySource());
        return array_shift($title);
//        osd($rootLayer);
//        osd($this->rootDisplaySource());
//        return $rootLayer->toValueList($this->rootDisplaySource())[0];

//        $result = $this->valueList($this->rootDisplaySource(), [$this->rootElement()]);
//        return $this->_resolveWrapper($result, $unwrap);
    }

    /**
     * Get the name of the displaySource (property or method) for capEntity
     *
     * This is the analog of Table::displayField.
     *
     * @return string
     * @throws BadClassConfigurationException
     */
    public function rootDisplaySource()
    {
        if ($this->rootDisplaySource === FALSE) {
            throw new BadClassConfigurationException(
                'A display source (rootDisplaySource) must be set for the '
                . 'root record in the stack entity ' . get_class($this));
        }
        return $this->rootDisplaySource;
    }

    /**
     * Get the name of the cap layer for this stackEntity
     *
     * @return string
     */
    public function rootLayerName()
    {
        if ($this->get('rootName') === FALSE) {
            throw new BadClassConfigurationException(
                'The name of the root entity ($this->rootName) must '
                . 'be set in the stack entity ' . get_class($this));
        }
        return $this->get('rootName');
    }
    //</editor-fold>

    /**
     * For an array with a single item, should it be unwrapped
     *
     * @param array $data
     * @param boolean $unwrap
     * @return string|array
     */
    protected function _resolveWrapper($data, $unwrap)
    {
        if ($unwrap) {
            $result = array_shift($data);
        } else {
            $result = $data;
        }
        return $result;
    }

    /**
     * For an array of entities, should they be made into a Layer
     *
     * It's possible for an empty array to come, so getting
     * the entity type is important to insure Layer can construct
     *
     * @param array $data
     * @param boolean $asArray
     * @return array|Layer
     */
    protected function _resolveReturnStructure($data, $asArray, $entityType)
    {
        if (!$asArray) {
            $data = layer($data, $entityType);
        }
        return $data;
    }

// <editor-fold defaultstate="collapsed" desc="LAYER ACCESS INTERFACE REALIZATION">

    /**
     * In a layer, get the entities linked to a specified record
     *
     * @throws UnknownLayerException
     * @param string $layer
     * @param array $options
     * @return LayerAccessArgs
     */
    public function linkedTo($foreign, $foreign_id, $layer = null)
    {
        if (!array_key_exists($layer, $this->schema)) {

            $className = get_class($this);
            $layers = array_keys($this->schema);
            $available = Text::toList($layers);

            $msg = "'The layer $layer doesn\'t exist in $className. '
                . 'Available choices are $available.'";

            throw new UnknownLayerException($msg);
        }
        return $this->$layer->linkedTo($foreign, $foreign_id);
    }

// </editor-fold>


    /**
     * Pass through for 'set' to handle Layer type columns
     *
     * If a layer value is set() directly with an array, this
     * overwrite will take care of it. New and patch entity do
     * the correct typing I think.
     *
     * {@inheritdoc}
     *
     * @param Layer $property
     * @param Layer $value
     * @param array $options
     * @return type
     */
    public function set($property, $value = null, array $options = [])
    {
        $typeMap = TableRegistry::getTableLocator()
            ->get($this->getSource())
            ->getSchema()
            ->typeMap();

        if (is_string($property)
            && Hash::extract($typeMap, $property) === ['layer']
            && !($value instanceof Layer)) {
            $value = $this->makeLayerObject($property, $value);

        } elseif (is_array($property)) {
            $typeMap = (Hash::filter($typeMap, function ($value) {
                return $value === 'layer';
            }));
            foreach ($typeMap as $p => $unused) {
                if (key_exists($p, $property)
                    && !($property[$p] instanceof Layer)) {
                    $property[$p] = $this->makeLayerObject($p, $property[$p]);
                }
            }
        }
        return parent::set($property, $value, $options);
    }

    private function makeLayerObject($layer, $seed)
    {
        try {
            $product = new Layer($seed);
            return $product;
        } catch (\Exception $ex) {
            $this->setError($layer, $ex->getMessage());
//            osd($this->getErrors());
            return new Layer([], $layer);
        }
    }

}
