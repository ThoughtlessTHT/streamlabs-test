<?php

namespace AppBundle\Controller;

use AppBundle\Security\User;
use AppBundle\Service\SocketService;
use AppBundle\Service\TwitchService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\HttpFoundation\Response;


class AuthenticationController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(TwitchService $twitchService)
    {
        $uri = $twitchService->getAuthorizationURI();

        return $this->render('default/index.html.twig', [
            'login_uri' => $uri
        ]);
    }

    /**
     * @Route("/callback", name="callback")
     */
    public function callbackAction(Request $request, TwitchService $twitchService) {
        $code = $request->query->get('code');

        $user = $twitchService->authorize($code);

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);
        $this->get('session')->set('_security_main', serialize($token));

        return $this->render('default/select.html.twig');
    }

    /**
     * @Route("/submit_streamer", name="submit")
     */
    public function submitAction(Request $request, TwitchService $twitchService)
    {
        /** @var User $user */
        $user = $this->getUser();

        $streamer = $request->get('streamer');

        $streamerID = $twitchService->getStreamerId($streamer, $user);

        $user->setStreamer($streamerID);
        $user->setStreamerName($streamer);

        $twitchService->subscribe($user);

        return $this->redirectToRoute('streamer');
    }

    /**
     * @Route("/streamer", name="streamer")
     */
    public function streamerAction()
    {

        return $this->render('default/streamer.html.twig');
    }

    /**
     * @Route("/webhook/{type}", name="webhhok")
     */
    public function webhookAction($type, Request $request, SocketService $socketService) {
        if ($request->getMethod() == 'GET') {
            return new Response($request->query->get('hub_challenge'));
        } else {
            $twitchMessage = json_decode($request->getContent());
            $data = $twitchMessage->data[0];

            switch ($type) {
                case 'follow':
                    $message = "{$data->from_name} started following {$data->to_name}";
                    $socketService->emit($type, $data->from_id, $message);
                    break;
                case 'follower':
                    $message = "{$data->from_name} started following {$data->to_name}";
                    $socketService->emit($type, $data->to_id, $message);
                    break;
                case 'user':
                    $message = "{$data->display_name} changed profile information";
                    $socketService->emit($type, $data->id, $message);
                    break;
                case 'stream':
                    if (!empty($data)) {
                        $message = "{$data->user_name} changed stream information";
                        $socketService->emit($type, $data->user_id, $message);
                        break;
                    }
                    break;
                default:
                    break;
            }

            return new Response();
        }
    }
}
