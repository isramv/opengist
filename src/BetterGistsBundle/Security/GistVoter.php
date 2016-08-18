<?php

namespace BetterGistsBundle\Security;

use BetterGistsBundle\Entity\Gist;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class GistVoter extends Voter {
  /**
   * edit voter
   */
  const EDIT = 'edit';
  /**
   * @param string $attribute
   * @param mixed $subject
   * @return bool
   */
  protected function supports($attribute, $subject) {
    if( !in_array($attribute, array(self::EDIT))) {
      return FALSE;
    }
    if(!$subject instanceof Gist) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @param string $attribute
   * @param mixed $subject
   * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
   * @return bool
   */
  protected function voteOnAttribute($attribute, $subject, TokenInterface $token) {
      $user = $token->getUser();

      if(!$user instanceof User) {
        return FALSE;
      }

      $gist = $subject;

      switch ($attribute) {
        case self::EDIT:
          return $this->canEdit($gist, $user);
      }

  }

  /**
   * @param \BetterGistsBundle\Entity\Gist $gist
   * @param \AppBundle\Entity\User $user
   * @return bool
   */
  private function canEdit(Gist $gist, User $user) {
    return $gist->getAuthor()->getId() === $user->getId();
  }

}