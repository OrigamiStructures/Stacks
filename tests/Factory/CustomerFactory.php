<?php
declare(strict_types=1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2020 Juan Pablo Ramirez and Nicolas Masson
 * @link          https://webrider.de/
 * @since         1.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Stacks\Test\Factory;

use CakephpFixtureFactories\Factory\BaseFactory;
use Faker\Generator;

class CustomerFactory extends BaseFactory
{
    protected function getRootTableRegistryName(): string
    {
        return 'TestPlugin.Customers';
    }

    protected function setDefaultTemplate(): void
    {
        $this->setDefaultData(function (Generator $faker) {
            return [
                'name' => $faker->lastName,
            ];
        });
    }

    /**
     * @param null $parameter
     * @param int $n
     * @return CustomerFactory
     */
    public function withBills($parameter = null, $n = 1): CustomerFactory
    {
        return $this->with('Bills', BillFactory::make($parameter, $n)->without('Customer'));
    }

    /**
     * @param null $parameter
     * @return CustomerFactory
     */
    public function withAddress($parameter = null): CustomerFactory
    {
        return $this->with('Address', AddressFactory::make($parameter));
    }
}
