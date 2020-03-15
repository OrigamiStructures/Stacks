<?php
namespace Stacks\Database\Type;

use Stacks\Model\Lib\Layer;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\Type as BaseType;
use Cake\Database\Type\ExpressionTypeInterface;

class LayerType extends BaseType implements ExpressionTypeInterface{

	public function toPHP($value, \Cake\Database\Driver $driver) {
		if ($value === null) {
            return null;
        }
        return unserialize($value);
	}

	public function marshal($value) {
		if (is_string($value)) {
			$value = unserialize($value);
		}
		if (is_array($value) && !empty($value)) {
			$value = new Layer($value);
		}
		if (is_object($value)) {
			return $value;
		}
		return NULL;
	}

    /**
     * Returns an ExpressionInterface object for the given value that can
     * be used in queries.
     *
     * @param mixed $value The value to be converted to an expression
     * @return \Cake\Database\ExpressionInterface
     */
    public function toExpression($value): ExpressionInterface
    {
        // TODO: Implement toExpression() method.
    }

}
