<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, [
                'label' => 'Titre',
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Entrez le titre de l\'article'
                ]
            ])
            ->add('content', null, [
                'label' => 'Contenu',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Rédigez votre article ici',
                    'rows' => 10,
                    'style' => 'resize: vertical;'
                ]
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Catégories',
                'label_attr' => [
                    'class' => 'fw-bold mb-3'
                ],
                'choice_attr' => function() {
                    return ['class' => 'form-check-input'];
                },
                'attr' => [
                    'class' => 'categories-checkboxes'
                ],
                'by_reference' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}