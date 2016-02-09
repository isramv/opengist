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
            ->add('namePrefix', ChoiceType::class,
                array('choices' => array(
                    'mr' => 'Mr.',
                    'ms' => 'Ms.',
                    'sr' => 'Sr.'
                ))
              )
            ->add('firstName')
            ->add('lastName')
            ->add('jobTitle')
            ->add('description')
            ->add('email')
            ->add('phone')
            ->add('twitter')
            ->add('facebook')
            ->add('linkedIn')
            ->add('birthday', DateType::class, array(
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'data' => new \DateTime('now', new \DateTimeZone('America/Los_Angeles'))
            ))
            ->add('gender', ChoiceType::class, array(
                'choices' => array(
                    'male' => 'Male',
                    'female' => 'Female',
                )
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
