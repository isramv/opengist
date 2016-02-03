<?php

namespace Rocket\Clients\ContactsBundle\Form;

use Symfony\Bridge\Doctrine\Tests\Form\ChoiceList\GenericEntityChoiceListTest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class ContactType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        date_default_timezone_set("America/Los_Angeles");
        $builder
            ->add('namePrefix')
            ->add('firstName')
            ->add('lastName')
            ->add('description')
            ->add('emails')
            ->add('phones')
            ->add('twitter')
            ->add('facebook')
            ->add('linkedIn')
            ->add('jobTitle')
            ->add('birthday', DateType::class, array(
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'data' => new \DateTime('now', new \DateTimeZone('America/Los_Angeles'))
            ))
            ->add('gender', ChoiceType::class, array(
                'choices' => array(
                    null => 'Don\'t know',
                    'male' => 'Male',
                    'female' => 'Female',
                )
            ))
            ->add('picture')
            ->add('created', DateType::class, array(
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'data' => new \DateTime('now', new \DateTimeZone('America/Los_Angeles'))
            ))
            ->add('organization')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Rocket\Clients\ContactsBundle\Entity\Contact'
        ));
    }
}
