<?php
namespace AOE\Crawler\Tests\Functional\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use AOE\Crawler\Domain\Repository\ProcessRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;

/**
 * Class ProcessRepositoryTest
 *
 * @package AOE\Crawler\Tests\Functional\Domain\Repository
 */
class ProcessRepositoryTest extends FunctionalTestCase
{

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['cms', 'core', 'frontend', 'version', 'lang', 'extensionmanager', 'fluid'];

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var ProcessRepository
     */
    protected $subject;

    /**
     * Creates the test environment.
     *
     */
    public function setUp()
    {
        parent::setUp();
        $this->importDataSet(dirname(__FILE__) . '/../../Fixtures/tx_crawler_process.xml');
        $this->subject = new ProcessRepository();
    }

    /**
     * @test
     *
     * @param $orderField
     * @param $orderDirection
     * @param $itemCount
     * @param $offset
     * @param $where
     * @param $expected
     *
     * @dataProvider findAllDataProvider
     */
    public function findAll($orderField, $orderDirection, $itemCount, $offset, $where, $expected)
    {
        $actual = $this->subject->findAll($orderField, $orderDirection, $itemCount, $offset, $where);

        $this->assertSame(
            $expected,
            $actual->getProcessIds()
        );
    }

    /**
     * @return array
     */
    public function findAllDataProvider()
    {
        return [
            'No Values set, all defaults will be used' => [
                'orderField' => '',
                'oderDirection' => '',
                'itemCount' => '',
                'offset' => '',
                'where' => '',
                'expected' => ['1004', '1003', '1002', '1001', '1000']
            ],
            'OrderField is set, rest of fields will be using default values' => [
                'orderField' => 'ttl',
                'oderDirection' => '',
                'itemCount' => '',
                'offset' => '',
                'where' => '',
                'expected' => ['1001', '1002', '1003', '1004', '1000']
            ],
            'OrderDirection is set, rest of fields will be using default values' => [
                'orderField' => '',
                'oderDirection' => 'ASC',
                'itemCount' => '',
                'offset' => '',
                'where' => '',
                'expected' => ['1000', '1001', '1002', '1003', '1004']
            ],
            'ItemCount is set, rest of fields will be using default values' => [
                'orderField' => '',
                'oderDirection' => '',
                'itemCount' => '2',
                'offset' => '',
                'where' => '',
                'expected' => ['1004', '1003']
            ],
            'Offset is set, rest of fields will be using default values' => [
                'orderField' => '',
                'oderDirection' => '',
                'itemCount' => '',
                'offset' => '1',
                'where' => '',
                'expected' => ['1003', '1002', '1001', '1000']
            ],
            'where is set, rest of fields will be using default values' => [
                'orderField' => '',
                'oderDirection' => '',
                'itemCount' => '',
                'offset' => '',
                'where' => 'ttl < 20',
                'expected' => ['1000']
            ],
            'All fields are set' => [
                'orderField' => 'process_id',
                'oderDirection' => 'ASC',
                'itemCount' => '1',
                'offset' => '1',
                'where' => 'process_id > 1000',
                'expected' => ['1002']
            ],
        ];
    }

    /**
     * @test
     */
    public function countActive()
    {
        $this->assertEquals(
            3,
            $this->subject->countActive()
        );
    }

    /**
     * @test
     */
    public function countNotTimeouted()
    {
        $this->assertEquals(
            2,
            $this->subject->countNotTimeouted(11)
        );
    }

    /**
     * @test
     */
    public function countAll()
    {
        $this->assertEquals(
            5,
            $this->subject->countAll()
        );
    }
}
