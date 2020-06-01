<?php
declare(strict_types=1);

namespace Stacks\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PeopleFixture
 */
class PeopleFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // phpcs:disable
    public $fields = [
        'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'person_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => 'SysAdmins are self linked. Can see all warehouses', 'precision' => null, 'autoIncrement' => null],
        'tenant_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => 'Warehouse workers see one warehouse', 'precision' => null, 'autoIncrement' => null],
        'warehouse_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => 'Tenant workers see one tenant', 'precision' => null, 'autoIncrement' => null],
        'first_name' => ['type' => 'char', 'length' => 75, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null],
        'last_name' => ['type' => 'char', 'length' => 75, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null],
        'role' => ['type' => 'char', 'length' => 50, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null],
        'parent_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'old_user_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'pronoun' => ['type' => 'char', 'length' => 25, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null],
        'cart_session' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => false, 'default' => null, 'comment' => ''],
        'modified' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => false, 'default' => null, 'comment' => ''],
        '_indexes' => [
            'last_name' => ['type' => 'index', 'columns' => ['last_name'], 'length' => ['last_name' => '10']],
            'first_name' => ['type' => 'index', 'columns' => ['first_name'], 'length' => ['first_name' => '10']],
            'person_id' => ['type' => 'index', 'columns' => ['person_id'], 'length' => []],
            'tenant_id' => ['type' => 'index', 'columns' => ['tenant_id'], 'length' => []],
            'warehouse_id' => ['type' => 'index', 'columns' => ['warehouse_id'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
            'people_ibfk_1' => ['type' => 'foreign', 'columns' => ['person_id'], 'references' => ['people', 'id'], 'update' => 'restrict', 'delete' => 'noAction', 'length' => []],
            'people_ibfk_2' => ['type' => 'foreign', 'columns' => ['tenant_id'], 'references' => ['tenants', 'id'], 'update' => 'restrict', 'delete' => 'cascade', 'length' => []],
            'people_ibfk_3' => ['type' => 'foreign', 'columns' => ['warehouse_id'], 'references' => ['warehouses', 'id'], 'update' => 'restrict', 'delete' => 'cascade', 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8_general_ci'
        ],
    ];
    // phpcs:enable
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
//        $this->records = [
//            [
//                'id' => 1,
//                'person_id' => 1,
//                'tenant_id' => 1,
//                'warehouse_id' => 1,
//                'first_name' => '',
//                'last_name' => '',
//                'role' => '',
//                'parent_id' => 1,
//                'old_user_id' => 1,
//                'pronoun' => '',
//                'cart_session' => '07d36659-b929-461c-9225-74b4ceaeeaf9',
//                'created' => '2020-05-31 19:04:39',
//                'modified' => '2020-05-31 19:04:39',
//            ],
//        ];
//        parent::init();
    }
}
