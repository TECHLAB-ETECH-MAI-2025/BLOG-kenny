<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Comment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CommentForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('article', EntityType::class, [
                'class' => Article::class,
                'choice_label' => 'title',
                'label' => 'Article',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un article'])
                ]
            ])
            ->add('author', TextType::class, [
                'label' => 'Auteur',
                'attr' => [
                    'placeholder' => 'Votre nom',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom de l\'auteur ne peut pas être vide']),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Commentaire',
                'attr' => [
                    'placeholder' => 'Écrivez votre commentaire ici',
                    'rows' => 5,
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le commentaire ne peut pas être vide']),
                    new Length([
                        'min' => 5,
                        'minMessage' => 'Le commentaire doit contenir au moins {{ limit }} caractères'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}