<?php
namespace App\Interfaces;

use App\Model\Lib\LayerAccessArgs;
use App\Model\Lib\LayerAccessProcessor;

/**
 * Interface LayerTaskInterface
 *
 * I don't expect to need to implement this interface again. It was made to organize
 * my thoughts about division of labor for the LayerAccess system.
 *
 * These are the core tasks for LayerAccessProcessor. Esentially, the class needs to be
 * able to recieve all the data accessess and organization specifications (setArgObj())
 * and then process the data based on these specs (perform()).
 *
 * It needs to do all this in a fluent way (find()).
 *
 * Finally, because the system can be used in a step-wise/manual way (as well as fluent
 * style), it can cloneArgObj(). This feature is used to insure that once an LAP is
 * configured with it's LAA, it won't change when an external agent modifies its LAA
 * by reference.
 *
 * @package App\Interfaces
 */
interface LayerTaskInterface
{

    /**
     * Initiate a fluent Access definition
     *
     * @todo This name has a collision. It will be changed later. (12/18/19 Really? Where?)
     * @return LayerAccessArgs
     */
    public function find();

    /**
     * Run the Access process and return an iterator containing the result
     *
     * @param $argObj LayerAccessArgs
     * @return LayerAccessProcessor
     */
    public function perform($argObj);

    /**
     * Store an the Access process instructions
     *
     * @param $argObj LayerAccessArgs
     * @return bool
     */
    public function setArgObj($argObj);

    /**
     * Get a copy of the Access instructions (with no included data)
     *
     * @return LayerAccessArgs
     */
    public function cloneArgObj();
}
