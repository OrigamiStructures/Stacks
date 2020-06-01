<?php
declare(strict_types=1);

/**
 *
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        Juan Pablo Ramirez
 * @author        Nicolas Masson
 * @link          https://github.com/pakacuda/cakephp-fixture-factories
 * @since         1.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Stacks\Test\Factory;

use Faker\Generator;
use CakephpFixtureFactories\Factory\BaseFactory as CakephpBaseFactory;

/**
 * userFactory
 */
class userFactory extends CakephpBaseFactory
{
    /**
     * Defines the Table Registry used to generate entities with
     * @return string
     */
    protected function getRootTableRegistryName(): string
    {
        return 'Stacks.users';
    }

    /**
     * Defines the default values of you factory. Usefull for
     * not nullable fields. You may use methods of the factory here
     * @return void
     */
    protected function setDefaultTemplate(): void
    {
        $this->setDefaultData(function(Generator $faker) {
            return [
                // set the model's default values
                // For example:
                // 'name' => $faker->lastName
            ];
        });
    }

}