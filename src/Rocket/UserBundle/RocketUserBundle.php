<?php

namespace Rocket\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class RocketUserBundle extends Bundle
{
  public function getParent() {
    return 'FOSUserBundle';
  }
}
