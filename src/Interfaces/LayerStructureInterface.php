<?php


namespace App\Interfaces;


use App\Model\Lib\LayerAccessArgs;
use App\Model\Lib\LayerAccessProcessor;

interface LayerStructureInterface
{

    /**
     * Gather the available data at this level and package the iterator
     *
     * @param $name The property name this layer is stored on in a StackEntity
     * @param $className $the Entity class stored in the Layer
     * @return LayerAccessProcessor
     */
    public function getLayer($name, $className);

    /**
     * Get an new LayerAccessArgs instance
     * @return LayerAccessArgs
     */
    public function getArgObj();

}
