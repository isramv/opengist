<?php

namespace BetterGistsBundle\EventListener;

use BetterGistsBundle\Controller\TokenAuthenticationController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

use Psecio\Jwt\Header;
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
    $request = $event->getRequest();

    if(!is_array($controller)) {
      return;
    }

    if($controller[0] instanceof TokenAuthenticationController) {

      $authorization_code = $request->headers->get('Authorization');

      $file_locator = new FileLocator(__DIR__.'/../conf');
      $token_config = $file_locator->locate('token_config.yml');
      $key = Yaml::parse($token_config);
      $token_key = $key['token_config']['phrase'];
      $header = new Header($token_key);
      $jwt = new JwtBetterGist($header);

      $jwt_encode = new JwtBetterGist($header);
      $jwt_encode
        ->issuedAt(time())
        ->notBefore(time()-600)
        ->expireTime(time()+3600);
      $result = $jwt_encode->encode();

      $jwt->verifyRequestString($authorization_code, $jwt);

      $response = new Response(
        'verified',
        Response::HTTP_OK,
        array('content-type' => 'application/json')
      );
    }
  }
}
