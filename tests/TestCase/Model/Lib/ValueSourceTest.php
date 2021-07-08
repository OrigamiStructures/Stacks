<?php


namespace Stacks\Test\TestCase\Model\Lib;


use Cake\TestSuite\TestCase;
use Stacks\Model\Lib\ValueSource;
use Stacks\Test\Factory\AuthorFactory;
use TestApp\Model\Entity\Author;

class ValueSourceTest extends TestCase
{

    /**
     * @dataProvider validConstructVariations
     */
    public function testValidConstructVariations($entity, $source, $expected)
    {
        $Entity = AuthorFactory::make(
            ['name' => 'value from property']
        )->getEntity();

        $ValueSource = new ValueSource($entity, $source);
        $this->assertEquals($expected['source'], $ValueSource->sourceNode());
        $this->assertEquals($expected['name'], $ValueSource->entityName());
        $this->assertEquals($expected['isValid'], $ValueSource->isValid());
        $this->assertEquals($expected['errors'], $ValueSource->getErrors());
        $this->assertEquals($expected['value'], $ValueSource->value($Entity));
    }

    public function validConstructVariations()
    {
        return [
            ['Author', 'name', [ //Entity alias and property
                'source' => 'name',
                'name' => 'Author',
                'isValid' => true,
                'errors' => [],
                'value' => 'value from property'
            ]],
            ['Author', 'entityMethod', [ //Entity alias and method
                'source' => 'entityMethod()',
                'name' => 'Author',
                'isValid' => true,
                'errors' => [],
                'value' => 'value from method'
            ]],
            ['author', 'entityMethod', [ //layer name and method
                'source' => 'entityMethod()',
                'name' => 'Author',
                'isValid' => true,
                'errors' => [],
                'value' => 'value from method'
            ]],
            [AuthorFactory::make()->getEntity(), 'entityMethod', [ //Entity object and method
                'source' => 'entityMethod()',
                'name' => 'Author',
                'isValid' => true,
                'errors' => [],
                'value' => 'value from method'
            ]],
            [Author::class, 'entityMethod', [ //namespaced entity name and method
                'source' => 'entityMethod()',
                'name' => 'Author',
                'isValid' => true,
                'errors' => [],
                'value' => 'value from method'
            ]],
            ['Author', 'ambiguous..', [ //specify property in ambiguous case
                'source' => 'ambiguous',
                'name' => 'Author',
                'isValid' => true,
                'errors' => [],
                'value' => 'value from ambiguous property'
            ]],
            ['Author', 'ambiguous()', [ //specify method in ambiguous case
                'source' => 'ambiguous()',
                'name' => 'Author',
                'isValid' => true,
                'errors' => [],
                'value' => 'value from ambiguous method'
            ]],

        ];
    }

    /**
     * @dataProvider invalidConstructVariations
     * @TODO Error handling in this class is a bit of a mess
     *     and far too complicated. Refactor to simplify.
     */
    public function testInvalidConstructVariations($entity, $source, $expected)
    {
        $Entity = AuthorFactory::make(['name' => 'value from property'])->getEntity();
        $ValueSource = new ValueSource($entity, $source);
        $this->assertEquals($expected['isValid'], $ValueSource->isValid());
        $this->assertEquals($expected['value'], $ValueSource->value($Entity));
        $this->assertIsString($ValueSource->getErrors()[0][0]);
    }

    public function invalidConstructVariations()
    {
        return [
            ['Author', 'ambiguous', [ //Entity alias and ambiguous source
                'isValid' => false,
                'value' => false
            ]],
            ['Author', 'unknown', [ //Entity alias and unknown source
                'isValid' => false,
                'value' => false
            ]],
        ];
    }
}