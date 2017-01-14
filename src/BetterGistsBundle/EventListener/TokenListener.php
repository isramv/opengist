<?php

namespace BetterGistsBundle\EventListener;

use BetterGistsBundle\Controller\TokenAuthenticationController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
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

  private $phrase;

  public function __construct($phrase) {
    $this->phrase = $phrase;
  }

  public function onKernelController(FilterControllerEvent $event)
  {

    $controller = $event->getController();

    if(!is_array($controller)) {
      return;
    }

    if($controller[0] instanceof TokenAuthenticationController) {

      $request = $event->getRequest();

      $auth_string = is_null($request->headers->get('authorization')) ? $request->headers->get('x-custom-auth') : $request->headers->get('authorization');

      $header = new Header($this->phrase);
      $jwt = new JwtBetterGist($header);

      try {
        $jwt->verifyRequestString($auth_string, $jwt);
      } catch (\Exception $e) {
        $event->getRequest()->attributes->set('error_token', $e);
      }
    }
  }

  /**
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   */
  public function onKernelResponse(FilterResponseEvent $event) {

    if(!is_null($event->getRequest()->attributes->get('exception'))) {
      $error = array(
        'status' => 'ERROR',
        'message' => 'Authentication failed'
      );
      $response = $event->getResponse();
      $response->setContent(json_encode($error));
      $response->headers->set('Content-Type', 'application/json');
    }

  }
}

