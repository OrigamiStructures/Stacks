<?php
namespace App\Model\Lib;

use Cake\Datasource\Paginator;

/**
 * StackPaginator
 *
 * Subclass Paginator used by PaginationComponent to make it operate
 * on Stack results
 *
 * @author dondrake
 */
class StackPaginator extends Paginator {

	/**
	 * Implement pagination on a stack table query
	 *
	 * The stack query is packaged in a callable. Then the pagination is
	 * also packaged in a callable and passed to the stack process.
	 * The reason: pagination must happen happen to a query that is
	 * created part way through stack assembly. Sending the pagination
	 * processes in as a callable allows it to be on the scene for this
	 * mid-stream use.
	 *
	 * @todo Does this method have to do any additional work to make
	 *		 $params and $settings work properly?
	 *
	 * @param Callable $findStackCallable
     * @param array $params Request params
     * @param array $settings The settings/configuration used for pagination.
	 * @return Query
	 */
    public function paginate($findStackCallable, array $params = [], array $settings = []) {

		$paginatorCallable = function($query) use ($params, $settings) {
			return parent::paginate($query, $params, $settings);
		};

		return $findStackCallable($paginatorCallable);

//		list($table, $Ids) = $object->distill($settings['seed'], $settings['ids']);
//		$query = $table->find('all')->where(['id IN' => $Ids]);
//
//		$result = parent::paginate($query, $params, $settings);
//
//		$IDs = $result->reduce(function($accum, $entity) {
//			$accum[] = $entity->id;
//			return $accum;
//		}, []);
//		return $object->stacksFromRoot($Ds);
    }

}
