<?php


namespace Stacks\Test;


use PHP_CodeSniffer\Tokenizers\PHP;
use Stacks\Model\Lib\Layer;
use Stacks\Model\Lib\LayerAccessArgs;
use Stacks\Model\Lib\LayerAccessProcessor;
use Stacks\Test\Factory\PersonFactory;
use Cake\ORM\TableRegistry;

class LayerAccessProcessorTest extends \Cake\TestSuite\TestCase
{

    /**
     * @var array of people entities
     */
    public $people;

    /**
     * @var layer
     */
    public $layer;

    /**
     * @var LayerAccessProcessor
     */
    public $processor;


    public $fixtures = [
        'app.people',
    ];


    public function setUp(): void
    {
        $entities = PersonFactory::make(10)->persist();
        $this->people = TableRegistry::getTableLocator()->get('People')->find()->toArray();
        $this->layer = new Layer($this->people);
        $this->processor = new LayerAccessProcessor('people', 'Person');
        $this->processor->insert($this->layer);
        parent::setUp();
    }

    public function tearDown(): void
    {
        unset($this->people, $this->layer);
        parent::tearDown();
    }

    public function testPerformFilter()
    {
        $argObj = (new LayerAccessArgs())
            ->specifyFilter('last_name', 'Holmes');
        $this->assertCount(5, $this->processor->perform($argObj)->toArray());
    }


    public function testPerformSort()
    {
        $argObj = (new LayerAccessArgs())
            ->specifySort('first_name', SORT_ASC);
        $people = $this->processor->perform($argObj);
        foreach ($people as $key => $person) {
            if($key+1 < 10) {
                $this->assertTrue($person->first_name <= $people[$key+1]->first_name);
            }
        }
    }

    public function testPerformPagination()
    {
        $this->markTestIncomplete();
    }

    public function testPerform()
    {
        $this->markTestIncomplete();
    }
}
