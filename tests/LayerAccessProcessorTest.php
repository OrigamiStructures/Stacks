<?php


namespace Stacks\Test;


use PHP_CodeSniffer\Tokenizers\PHP;
use Stacks\Model\Lib\Layer;
use Stacks\Model\Lib\LayerAccessArgs;
use Stacks\Model\Lib\LayerAccessProcessor;
use Stacks\Test\Factory\AuthorFactory;
use Stacks\Test\Factory\CountryFactory;
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
//        'app.people',
    ];


    public function setUp(): void
    {
//        debug(TableRegistry::getTableLocator()->get('Authors'));
        $entities = AuthorFactory::make(10)->persist();
        $this->people = TableRegistry::getTableLocator()->get('Authors')->find()->toArray();
        $this->layer = new Layer($this->people);
//        debug($this->layer);
        $this->processor = new LayerAccessProcessor('author', 'Author');
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
        $this->assertTrue($argObj->hasFilter());
        $this->assertCount(5, $this->processor->perform($argObj)->toArray(),
            'Filtering did not produce the expected result size');
    }

    public function testPerformSort()
    {
        $argObj = (new LayerAccessArgs())
            ->specifySort('first_name', SORT_ASC);
        $this->assertTrue($argObj->hasSort());
        $people = $this->processor->perform($argObj);
        foreach ($people as $key => $person) {
            if($key+1 < 10) {
                $this->assertTrue($person->first_name <= $people[$key+1]->first_name,
                    'Sorting did not produce the expected ascending order of strings');
            }
        }
    }

    public function testPerformPagination()
    {
        $argObj = (new LayerAccessArgs())
            ->specifyPagination(2, 2);
        $this->assertTrue($argObj->hasPagination());
        $page = $this->processor->perform($argObj);
        $this->assertCount(2, $page,
            'Pagination did not retun the expected number records in a page');
    }

    public function testPerform()
    {
        $argObj = (new LayerAccessArgs())
            ->specifyFilter('last_name', 'Watson')
            ->specifySort('first_name', SORT_ASC)
            ->specifyPagination(1, 3);
        $this->assertTrue($argObj->hasFilter(),
            'Filter did not set properly');
        $this->assertTrue($argObj->hasPagination(),
            'Pagination did not set properly');
        $this->assertTrue($argObj->hasSort(),
            'Sort did not set propery');

        $people = $this->processor->perform($argObj);

        $this->assertCount(3, $people,
            '\'perform\' did not paginate to the expected number of records');
        foreach ($people as $key => $person) {
            if($key+1 < 3) {
                $this->assertTrue($person->last_name === 'Watson',
                    '\'perform()\' properly filter the records');
                $this->assertTrue($person->first_name <= $people[$key+1]->first_name,
                    'The sort order after \'perform()\' is not correct');
            }
        }

    }
}
