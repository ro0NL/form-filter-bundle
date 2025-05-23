<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\Filter\Doctrine;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterOperands;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\BooleanFilterType;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter\FormType;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter\ItemCallbackFilterType;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter\ItemFilterType;
use Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Filter\RangeFilterType;
use Spiriit\Bundle\FormFilterBundle\Tests\TestCase;

/**
 * Filter query builder tests.
 */
abstract class DoctrineQueryBuilderUpdater extends TestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Connection
     */
    protected $conn;

    public function setUp(): void
    {
        parent::setUp();

        $this->em = $this->getSqliteEntityManager();
        $this->conn = $this->em->getConnection();
    }

    /**
     * @return QueryBuilder
     */
    abstract protected function createDoctrineQueryBuilder();

    /**
     * Get query parameters from the query builder.
     *
     * @param $qb
     * @return array
     */
    protected function getQueryBuilderParameters($qb)
    {
        if ($qb instanceof \Doctrine\DBAL\Query\QueryBuilder) {
            return $qb->getParameters();
        }

        if ($qb instanceof QueryBuilder) {
            $params = [];

            foreach ($qb->getParameters() as $parameter) {
                $params[$parameter->getName()] = $parameter->getValue();
            }

            return $params;
        }

        return [];
    }

    protected function createBuildQueryTest($method, array $dqls)
    {
        $form = $this->formFactory->create(ItemFilterType::class);
        $filterQueryBuilder = $this->initQueryBuilderUpdater();

        // without binding the form
        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();

        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);
        $this->assertEquals($dqls[0], $doctrineQueryBuilder->{$method}());

        // bind a request to the form - 1 params
        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'blabla', 'position' => '']);

        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);
        $this->assertEquals($dqls[1], $doctrineQueryBuilder->{$method}());

        // bind a request to the form - 2 params
        $form = $this->formFactory->create(ItemFilterType::class);

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'blabla', 'position' => 2]);

        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);
        $this->assertEquals($dqls[2], $doctrineQueryBuilder->{$method}());
        $this->assertEquals(['p_i_position' => 2], $this->getQueryBuilderParameters($doctrineQueryBuilder));

        // bind a request to the form - 3 params
        $form = $this->formFactory->create(ItemFilterType::class);

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'blabla', 'position' => 2, 'enabled' => BooleanFilterType::VALUE_YES]);

        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);
        $this->assertEquals($dqls[3], $doctrineQueryBuilder->{$method}());
        $this->assertEquals(['p_i_position' => 2, 'p_i_enabled' => true], $this->getQueryBuilderParameters($doctrineQueryBuilder));

        // bind a request to the form - 3 params (use checkbox for enabled field)
        $form = $this->formFactory->create(ItemFilterType::class, null, ['checkbox' => true]);

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'blabla', 'position' => 2, 'enabled' => 'yes']);

        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);
        $this->assertEquals($dqls[4], $doctrineQueryBuilder->{$method}());
        $this->assertEquals(['p_i_position' => 2, 'p_i_enabled' => 1], $this->getQueryBuilderParameters($doctrineQueryBuilder));

        // bind a request to the form - date + pattern selector
        $year = \date('Y');
        $form = $this->formFactory->create(ItemFilterType::class, null, ['with_selector' => true]);

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(
            [
                'name' => [
                    'text' => 'blabla',
                    'condition_pattern' => FilterOperands::STRING_ENDS
                ],
                'position' => [
                    'text' => 2,
                    'condition_operator' => FilterOperands::OPERATOR_LOWER_THAN_EQUAL
                ],
                'createdAt' => [
                    'year' => $year,
                    'month' => 9,
                    'day' => 27
                ]
            ]
        );

        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);
        $this->assertEquals($dqls[5], $doctrineQueryBuilder->{$method}());
        $this->assertEquals(['p_i_position' => 2, 'p_i_createdAt' => new DateTime("{$year}-09-27")], $this->getQueryBuilderParameters($doctrineQueryBuilder));

        // bind a request to the form - datetime + pattern selector
        $form = $this->formFactory->create(ItemFilterType::class, null, ['with_selector' => true, 'datetime' => true]);

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => ['text' => 'blabla', 'condition_pattern' => FilterOperands::STRING_ENDS], 'position' => ['text' => 2, 'condition_operator' => FilterOperands::OPERATOR_LOWER_THAN_EQUAL], 'createdAt' => ['date' => ['year' => $year, 'month' => 9, 'day' => 27], 'time' => ['hour' => 13, 'minute' => 21]]]);

        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);
        $this->assertEquals($dqls[6], $doctrineQueryBuilder->{$method}());
        $this->assertEquals(['p_i_position' => 2, 'p_i_createdAt' => new DateTime("{$year}-09-27 13:21:00")], $this->getQueryBuilderParameters($doctrineQueryBuilder));
    }

    protected function createDisabledFieldTest($method, array $dqls)
    {
        $form = $this->formFactory->create(ItemFilterType::class, null, ['with_selector' => false, 'disabled_name' => true]);
        $filterQueryBuilder = $this->initQueryBuilderUpdater();

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'blabla', 'position' => 2]);

        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);
        $this->assertEquals($dqls[0], $doctrineQueryBuilder->{$method}());
    }

    protected function createApplyFilterOptionTest($method, array $dqls)
    {
        $form = $this->formFactory->create(ItemCallbackFilterType::class);
        $filterQueryBuilder = $this->initQueryBuilderUpdater();

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'blabla', 'position' => 2]);

        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);
        $this->assertEquals($dqls[0], $doctrineQueryBuilder->{$method}());
    }

    protected function createNumberRangeTest($method, array $dqls)
    {
        // use filter type options
        $form = $this->formFactory->create(RangeFilterType::class);
        $filterQueryBuilder = $this->initQueryBuilderUpdater();

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['position' => ['left_number' => 1, 'right_number' => 3]]);

        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);
        $this->assertEquals($dqls[0], $doctrineQueryBuilder->{$method}());
        $this->assertEquals(['p_i_position_left' => 1, 'p_i_position_right' => 3], $this->getQueryBuilderParameters($doctrineQueryBuilder));
    }

    protected function createNumberRangeCompoundTest($method, array $dqls)
    {
        // use filter type options
        $form = $this->formFactory->create(RangeFilterType::class);
        $filterQueryBuilder = $this->initQueryBuilderUpdater();

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['position_selector' => ['left_number' => ['text' => 4, 'condition_operator' => FilterOperands::OPERATOR_GREATER_THAN], 'right_number' => ['text' => 8, 'condition_operator' => FilterOperands::OPERATOR_LOWER_THAN_EQUAL]]]);

        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);
        $this->assertEquals($dqls[0], $doctrineQueryBuilder->{$method}());
        $this->assertEquals(['p_i_position_selector_left' => 4, 'p_i_position_selector_right' => 8], $this->getQueryBuilderParameters($doctrineQueryBuilder));
    }

    protected function createNumberRangeDefaultValuesTest($method, array $dqls)
    {
        // use filter type options
        $form = $this->formFactory->create(RangeFilterType::class);
        $filterQueryBuilder = $this->initQueryBuilderUpdater();

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['default_position' => ['left_number' => 1, 'right_number' => 3]]);

        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);
        $this->assertEquals($dqls[0], $doctrineQueryBuilder->{$method}());
        $this->assertEquals(['p_i_default_position_left' => 1, 'p_i_default_position_right' => 3], $this->getQueryBuilderParameters($doctrineQueryBuilder));
    }

    protected function createDateRangeTest($method, array $dqls)
    {
        // use filter type options
        $form = $this->formFactory->create(RangeFilterType::class);
        $filterQueryBuilder = $this->initQueryBuilderUpdater();

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['createdAt' => ['left_date' => '2012-05-12', 'right_date' => ['year' => '2012', 'month' => '5', 'day' => '22']]]);

        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);
        $this->assertEquals($dqls[0], $doctrineQueryBuilder->{$method}());
    }

    protected function createDateRangeWithTimezoneTest($method, array $dqls)
    {
        // same dates
        $form = $this->formFactory->create(RangeFilterType::class);
        $form->submit(['startAt' => ['left_date' => '2015-10-20', 'right_date' => '2015-10-20']]);

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();

        $filterQueryBuilder = $this->initQueryBuilderUpdater();
        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);
        $this->assertEquals($dqls[0], $doctrineQueryBuilder->{$method}());

        // different dates
        $form = $this->formFactory->create(RangeFilterType::class);
        $form->submit(['startAt' => ['left_date' => '2015-10-01', 'right_date' => '2015-10-16']]);

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();

        $filterQueryBuilder = $this->initQueryBuilderUpdater();
        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);
        $this->assertEquals($dqls[1], $doctrineQueryBuilder->{$method}());
    }

    public function createDateTimeRangeTest($method, array $dqls): void
    {
        // use filter type options
        $form = $this->formFactory->create(RangeFilterType::class);
        $filterQueryBuilder = $this->initQueryBuilderUpdater();

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit([
            'updatedAt' => [
                'left_datetime' => [
                    'date' => '2012-05-12',
                    'time' => '14:55'
                ],
                'right_datetime' => [
                    'date' => [
                        'year' => '2012',
                        'month' => '6',
                        'day' => '10'
                    ],
                    'time' => [
                        'hour' => 22,
                        'minute' => 12
                    ]
                ]
            ]
        ]);

        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);
        $this->assertEquals($dqls[0], $doctrineQueryBuilder->{$method}());
    }

    public function createFilterStandardTypeTest($method, array $dqls): void
    {
        $form = $this->formFactory->create(FormType::class);
        $filterQueryBuilder = $this->initQueryBuilderUpdater();

        $doctrineQueryBuilder = $this->createDoctrineQueryBuilder();
        $form->submit(['name' => 'hey dude', 'position' => 99]);

        $filterQueryBuilder->addFilterConditions($form, $doctrineQueryBuilder);
        $this->assertEquals($dqls[0], $doctrineQueryBuilder->{$method}());
    }
}
