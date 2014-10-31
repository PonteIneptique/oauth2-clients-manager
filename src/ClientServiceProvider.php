<?php

namespace Perseids\ClientsManager;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Silex\ServiceControllerResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\Voter\RoleHierarchyVoter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContextInterface;


use Doctrine\ORM\EntityManager;
use Perseids\ClientsManager\Entity\ModelManagerFactory;
use Perseids\ClientsManager\ClientEntityManager;

class ClientServiceProvider implements ServiceProviderInterface, ControllerProviderInterface
{

    protected $em;

    /**
     * Instantiate the Class
     * @param ModelManagerFactoryInterface $modelManagerFactory A AuthBucket Model Manager instance
     */
    public function __construct(Application $app) {
        $this->em = new ClientEntityManager($app);
    }

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Application $app An Application instance
     */
    public function register(Application $app)
    {
        $this->modelManagerFactory = $app['clients.model_manager.factory'];

        // Default options.
        $app['clients.options.default'] = array(
            // Specify custom view templates here.
            'templates' => array(
                'view' => '@clients/view.twig',
                'edit' => '@clients/edit.twig',
                'list' => '@clients/list.twig',
                'create' => '@clients/create.twig',
                'remove' => '@clients/remove.twig',
            )
        );


        // Initialize $app['clients.options'].
        $app['clients.options.init'] = $app->protect(function() use ($app) {
            $options = $app['clients.options.default'];

            if (isset($app['clients.options'])) {
                $options = array_replace_recursive($options, $app['clients.options']);
            }

            $app['clients.options'] = $options;
        });

        // User controller service.
        $app['clients.controller'] = $app->share(function ($app) {
            //$app['clients.options.init']();
            $controller = new ClientController($app['clients.model_manager.factory']);

            return $controller;
        });

        /*
         *  Not sure it goes there
         */
        $app->before(function () use ($app) {
            $app['twig']->addGlobal('layout', null);
            $app['twig']->addGlobal('layout', $app['twig']->loadTemplate('layout.twig'));
        });

    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {
        // Add twig template path.
        if (isset($app['twig.loader.filesystem'])) {
            $app['twig.loader.filesystem']->addPath(__DIR__ . '/views/', 'clients');
        }
    }

    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     * @throws \LogicException if ServiceController service provider is not registered.
     */
    public function connect(Application $app)
    {
        if (!$app['resolver'] instanceof ServiceControllerResolver) {
            // using RuntimeException crashes PHP?!
            throw new \LogicException('You must enable the ServiceController service provider to be able to use these routes.');
        }

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];


        $controllers->method('GET|POST')->match('/create', 'clients.controller:createAction')
            ->bind('clients.create');

        $controllers->method('GET|POST')->match('/edit/{id}', 'clients.controller:editAction')
            ->bind('clients.edit');

        $controllers->method('GET|POST')->match('/delete/{id}', 'clients.controller:deleteAction')
            ->bind('clients.remove');

        $controllers->get('/list', 'clients.controller:listAction')
            ->bind('clients.list');

        $controllers->get('/view/{id}', 'clients.controller:viewAction')
            ->bind('clients.view');
/*
        $controllers->get('', 'clients.controller:listAction')
            ->bind('clients.home');
*/
        return $controllers;
    }
}
