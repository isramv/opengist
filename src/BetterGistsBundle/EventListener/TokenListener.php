<?php

namespace BetterGistsBundle\EventListener;

use BetterGistsBundle\Controller\TokenAuthenticationController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

use Psecio\Jwt\Header;
use Psecio\Jwt\Claim;
use BetterGistsBundle\DependencyInjection\JwtBetterGist;
// Yml
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;


class TokenListener
{
  public function onKernelController(FilterControllerEvent $event)
  {

    $controller = $event->getController();

    if(!is_array($controller)) {
      return;
    }

    if($controller[0] instanceof TokenAuthenticationController) {

      $request = $event->getRequest();
      $authorization_code = $request->headers->get('Authorization');

      // Getting the keys
      $file_locator = new FileLocator(__DIR__.'/../conf');
      $token_config = $file_locator->locate('token_config.yml');
      $key = Yaml::parse($token_config);
      $token_key = $key['token_config']['phrase'];
      // TODO have several key_ids and phrases.
      $token_key_id = $key['token_config']['kid'];

      // JWT
      $header = new Header($token_key);
      $jwt = new JwtBetterGist($header);
      try {
        $jwt->verifyRequestString($authorization_code, $jwt);
      } catch (\Exception $e) {
        $error_message = $e->getMessage();
      }
      if(isset($error_message)) {
        throw new \Exception($error_message);
      }
    }
  }
}

