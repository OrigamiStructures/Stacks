<?php


namespace Stacks\Interfaces;


use Cake\ORM\Entity;
use Stacks\Model\Lib\LayerAccessArgs;
use Stacks\Model\Lib\LayerAccessProcessor;

interface LayerStructureInterface
{

    /**
     * Gather the available data at this level and package the iterator
     *
     * @param string $name The property name this layer is stored on in a StackEntity
     * @param Entity $className the Entity class stored in the Layer
     * @return LayerAccessProcessor
     */
    public function getLayer($name, $className);

    /**
     * Get an new LayerAccessArgs instance
     * @return LayerAccessArgs
     */
    public function getArgObj();

}
