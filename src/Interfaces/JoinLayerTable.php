<?php


namespace App\Interfaces;


use Cake\ORM\Query;

/**
 * Interface JoinLayerTable
 *
 * Any join table (tables that contain links) that is used as a layer
 * will be of limited use in rendering because the links/ids have no
 * meaning to human users. These layers need an additional process to
 * add display value data.
 *
 * This interface names the method that carries out the additional process
 *
 * @package App\Interfaces
 */
interface JoinLayerTable
{

    /**
     * Add human readable display values to a join record
     *
     * This makes join records used as layers in stacks more useful
     *
     * @param Query $query
     * @return array
     */
    public function hydrateLayer(Query $query);
}
