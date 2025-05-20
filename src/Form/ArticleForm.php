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
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le titre de l\'article'
                ]
            ])
            ->add('content', null, [
                'label' => 'Contenu',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Rédigez votre article ici',
                    'rows' => 10
                ]
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'label' => 'Catégories',
                'attr' => [
                    'class' => 'form-select select2',
                    'data-placeholder' => 'Sélectionnez les catégories...',
                    'multiple' => 'multiple'
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