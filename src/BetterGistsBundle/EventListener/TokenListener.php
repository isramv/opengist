<?php

namespace BetterGistsBundle\EventListener;

use BetterGistsBundle\Controller\TokenAuthenticationController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

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

    if($controller instanceof TokenAuthenticationController) {

      $request = $event->getRequest();


      $authorization_code = $request->headers->get('Authorization');
      $file_locator = new FileLocator(__DIR__.'/../conf');
      $token_config = $file_locator->locate('token_config.yml');
      $key = Yaml::parse($token_config);
      $token_key = $key['token_config']['phrase'];
      $token_key_id = $key['token_config']['kid'];
      $header = new Header($token_key);
      $jwt = new JwtBetterGist($header);

      /**
       * creating test tokens.
       */
      $jwt_encode = new JwtBetterGist($header);
      $claim = new Claim\Custom($token_key_id, 'kid');

      $jwt_encode
        ->addClaim($claim)
        ->issuedAt(time())
        ->notBefore(time()-600)
        ->expireTime(time()+3600);
      $token = $jwt_encode->encode();

      dump($token);
      dump($authorization_code);
      dump($jwt);
      dump(base64_decode($authorization_code));

      // End creating test tokens.

      $jwt->verifyRequestString($authorization_code, $jwt);

      $response = new Response(
        'verified',
        Response::HTTP_OK,
        array('content-type' => 'application/json')
      );


    }
  }
}
