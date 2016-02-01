<?php

namespace Rocket\Clients\ContactsBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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
            ->add('birthday', 'datetime')
            ->add('gender')
            ->add('picture')
            ->add('created', 'datetime')
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
