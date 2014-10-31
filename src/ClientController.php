<?php

namespace Perseids\ClientsManager;

use Perseids\ClientsManager\Entity\ModelManagerFactory;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use InvalidArgumentException;
use JasonGrimes\Paginator;

/**
 * Controller with actions for handling form-based authentication and user management.
 *
 * @package SimpleUser
 */
class ClientController 
{
	   /** @var ClientManager */
    protected $clientManager;
    protected $modelManagerFactory;

    protected $templates = array(
        'view' => '@clients/view.twig',
        'edit' => '@clients/edit.twig',
        'list' => '@clients/list.twig',
        'create' => '@clients/create.twig',
        'remove' => '@clients/remove.twig',
    );

    /**
     * @param string $key
     * @return string|null
     */
    public function getTemplate($key)
    {
        return $this->templates[$key];
    }

    /**
     * [__construct description]
     * @param ModelManagerFactoryInterface $modelManagerFactory [description]
     */
    public function __construct(ModelManagerFactory $modelManagerFactory = null)
    {
        $this->modelManagerFactory = $modelManagerFactory;
        $this->clientManager = $this->modelManagerFactory->getModelManager('client');
    }

    /**
     * Generate a Secret ID string
     */
    public function GenerateSecretId() {
        return substr(hash("sha512", uniqid(null, true)), 0, 64);
    }

    /**
     * Generate a Client ID String
     */
    public function GenerateClientId() {
        return substr(md5(uniqid(null, true)), 0, 8);
    }

    public function listAction(Application $app, Request $request) {
        $order_by = $request->get('order_by') ?: 'clientId';
        $order_dir = $request->get('order_dir') == 'DESC' ? 'DESC' : 'ASC';
        $limit = (int)($request->get('limit') ?: 50);
        $page = (int)($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = array();

        # public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
        $clients = $this->clientManager->findBy(
            $criteria, 
            array(  //Order Array => array(fieldname, direction)
                $order_by => $order_dir
            ),
            $limit,
            $offset
        );
        $numResults = $this->clientManager->findCount($criteria);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('user.list') . '?page=(:num)&limit=' . $limit . '&order_by=' . $order_by . '&order_dir=' . $order_dir
        );

        return $app['twig']->render($this->getTemplate('list'), array(
            "clients" => $clients,
            "paginator" => $paginator
        ));
    }

    public function deleteAction(Application $app, Request $request, $id) {
        $errors = array();
        $criteria = array("id" => $id);
        $client = $this->clientManager->findOneBy(
            $criteria
        );

        if ($request->isMethod('POST') && $id == $request->request->get('clientid')) {
            /*
             * Fetching the POST ID and comparing it with the get onee
             */
            $this->clientManager->delete($client);
            return $app->redirect($app['url_generator']->generate('clients.list'));
        }

        return $app['twig']->render($this->getTemplate('remove'), array(
            "client" => $client,
            "error" => $errors
        ));
    }


    public function editAction(Application $app, Request $request, $id) {
        $errors = array();
        $criteria = array("id" => $id);
        $client = $this->clientManager->findOneBy(
            $criteria
        );

        if ($request->isMethod('POST')) {
          	if($request->request->get('regenerateSecrete') == "regenerate") {
            	$client->setClientSecret($this->GenerateSecretId());

          	}
            $client->setRedirectUri($request->request->get('redirectUri'));
            $this->clientManager->update($client);
            return $app->redirect($app['url_generator']->generate('clients.view', array('id' => $client->getId())));
        }

        return $app['twig']->render($this->getTemplate('edit'), array(
            "client" => $client,
            "error" => $errors
        ));
    }

    public function createAction(Application $app, Request $request) {
        $errors = array();

        $client = $this->clientManager->create();

        if ($request->isMethod('POST')) {

            $client ->setClientSecret($this->GenerateSecretId())
                    ->setClientId($this->GenerateClientId())
                    ->setName($request->request->get('clientName'))
                    ->setDescription($request->request->get('clientDescription'))
                    ->setRedirectUri($request->request->get('redirectUri'));

            $this->clientManager->update($client);
            return $app->redirect($app['url_generator']->generate('clients.view', array('id' => $client->getId())));
        }

        return $app['twig']->render($this->getTemplate('create'), array(
            "client" => $client,
            "error" => $errors
        ));
    }

    public function viewAction(Application $app, Request $request, $id) {
        $criteria = array("id" => $id);
        $client = $this->clientManager->findOneBy(
            $criteria
        );

        return $app['twig']->render($this->getTemplate('view'), array(
            "client" => $client
        ));
    }

    public function home(Application $app, Request $request) {
        return $app->redirect($app['url_generator']->generate('clients.list'));
    }
}