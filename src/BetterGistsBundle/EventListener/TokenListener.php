<?php

namespace BetterGistsBundle\EventListener;

use BetterGistsBundle\Controller\TokenAuthenticationController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

use Psecio\Jwt\Header;
use BetterGistsBundle\DependencyInjection\JwtBetterGist;


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
      $key = 'DRUPAL';
      $header = new Header($key);
      $jwt = new JwtBetterGist($header);
      $jwt
        ->audience('http://myapp.local')
        ->issuedAt(time())
        ->notBefore(time()-600)
        ->expireTime(time()+3600);
      $result = $jwt->encode();

      $jwt->verifyRequestString($authorization_code, $jwt);

      $response = new Response(
        'verified',
        Response::HTTP_OK,
        array('content-type' => 'application/json')
      );
    }
  }
}
