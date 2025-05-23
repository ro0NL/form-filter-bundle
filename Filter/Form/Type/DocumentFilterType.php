<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Filter type for MongoDB documents.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DocumentFilterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setAttribute('filter_options', ['reference_type' => $options['reference_type'], 'reference_name' => $options['reference_name'] ?? ucfirst($builder->getName())]);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(['required' => false, 'data_extraction_method' => 'default', 'reference_type' => 'one', 'reference_name' => null])
            ->setRequired(['reference_type'])
            ->setAllowedValues('data_extraction_method', ['default'])
            ->setAllowedValues('reference_type', ['one', 'many'])
        ;
    }

    public function getParent(): ?string
    {
        return DocumentType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'filter_document';
    }
}
