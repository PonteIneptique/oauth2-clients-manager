<?php

namespace Perseids\ClientsManager\Clients;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Silex\ServiceControllerResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\Voter\RoleHierarchyVoter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Perseids\ClientsManager\Entity\ModelManagerFactory;

class ClientServiceProvider implements ServiceProviderInterface, ControllerProviderInterface
{

    /**
     * Instantiate the Class
     * @param ModelManagerFactoryInterface $modelManagerFactory A AuthBucket Model Manager instance
     */
    public function __construct(ModelManagerFactory $modelManagerFactory = null)
    {
        $this->modelManagerFactory = $modelManagerFactory;
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
        // Default options.
        
        $app['clients.options.default'] = array(
            // Specify custom view templates here.
            'templates' => array(
                'view' => '@clients/view.twig',
                'edit' => '@clients/edit.twig',
                'list' => '@clients/list.twig',
            )
        );


        // Initialize $app['user.options'].
        $app['user.options.init'] = $app->protect(function() use ($app) {
            $options = $app['user.options.default'];
            if (isset($app['user.options'])) {
                // Merge default and configured options
                $options = array_replace_recursive($options, $app['user.options']);

                // Migrate deprecated options for backward compatibility
                if (isset($app['user.options']['viewTemplate']) && !isset($app['user.options']['templates']['view'])) {
                    $options['templates']['view'] = $app['user.options']['viewTemplate'];
                }
                if (isset($app['user.options']['editTemplate']) && !isset($app['user.options']['templates']['edit'])) {
                    $options['templates']['edit'] = $app['user.options']['editTemplate'];
                }
                if (isset($app['user.options']['listTemplate']) && !isset($app['user.options']['templates']['list'])) {
                    $options['templates']['list'] = $app['user.options']['listTemplate'];
                }
            }
            $app['user.options'] = $options;
        });

        // User controller service.
        $app['clients.controller'] = $app->share(function ($app) {
            //$app['clients.options.init']();
            $controller = new ClientController($this->modelManagerFactory);

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

        $controllers->method('GET|POST')->match('/edit/{id}', 'clients.controller:editAction')
            ->bind('clients.edit');

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
