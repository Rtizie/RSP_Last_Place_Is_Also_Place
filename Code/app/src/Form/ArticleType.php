<?php
namespace App\Form;

use App\Entity\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Titulek je první pole formuláře
            ->add('title', TextType::class, [
                'label' => 'Titulek',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control']
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Popis článku',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Kategorie článku',
                'label_attr' => ['class' => 'form-label'],
                'choices' => [
                    'Novinky' => 'news',
                    'Technologie' => 'tech',
                    'Kultura' => 'culture',
                    'Sport' => 'sport',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('image', FileType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Nahrát obrázek',
                'label_attr' => ['class' => 'form-label'],
                'attr' => [
                    'class' => 'form-control-file'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
