<?php

namespace AppBundle\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class BetterGistUserBundle extends Bundle {

  public function getParent() {
    return 'FOSUserBundle';
  }

}