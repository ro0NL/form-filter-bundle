<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\Form;

use Closure;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Define filtering options.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FilterTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (null !== $options['apply_filter']) {
            $builder->setAttribute('apply_filter', $options['apply_filter']);
        }

        if ($options['filter_condition_builder'] instanceof Closure) {
            $builder->setAttribute('filter_condition_builder', $options['filter_condition_builder']);
        }

        if (null !== $options['filter_field_name']) {
            $builder->setAttribute('filter_field_name', $options['filter_field_name']);
        }

        if (null !== $options['filter_shared_name']) {
            $builder->setAttribute('filter_shared_name', $options['filter_shared_name']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['apply_filter' => null, 'data_extraction_method' => 'default', 'filter_condition_builder' => null, 'filter_field_name' => null, 'filter_shared_name' => null]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType(): string
    {
        return FormType::class;
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
