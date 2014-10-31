<?php
	namespace Perseids\ClientsManager;

	use Silex\Application;
	use Doctrine\Common\Annotations\AnnotationReader;
	use Doctrine\Common\Cache\FilesystemCache;
	use Doctrine\ORM\EntityManager;
	use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
	use Doctrine\ORM\Tools\Setup;

	use Perseids\ClientsManager\Entity\ModelManagerFactory;

	class ClientEntityManager {

    	public function __construct(Application $app) {
    		
	        $app['doctrine.orm.clients_entity_manager'] = $app->share(function ($app) {
	            $conn = $app['dbs']['default'];
	            $em = $app['dbs.event_manager']['default'];
	           
	            $isDevMode = false;
	            $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__.'/Entity'), $isDevMode, null, null, false);

	            return EntityManager::create($conn, $config, $em);
	        });

	        $app["clients.model"] = array(
	            'client' => 'Perseids\\ClientsManager\\Entity\\Client'
	        );

	        $app['clients.model_manager.factory'] = $app->share(function ($app) {
	            return new ModelManagerFactory(
	                $app['doctrine.orm.clients_entity_manager'],
	                $app['clients.model']
	            );
	        });
		}
	}