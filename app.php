<?php
	require_once __DIR__."/Controllers/Perseids/oAuth2.php";


	foreach (glob(__DIR__."/Entity/*.php") as $filename)
	{
	    require_once $filename;
	}
	
	$app = new Silex\Application(); 
	/* Register needed by authbucket oauth-2 */
	$app->register(new Silex\Provider\SecurityServiceProvider());
	$app->register(new Silex\Provider\SerializerServiceProvider());
	$app->register(new Silex\Provider\ServiceControllerServiceProvider());
	$app->register(new Silex\Provider\ValidatorServiceProvider());

	/*Register Needed by SimpleUser*/
	$app->register(new Silex\Provider\DoctrineServiceProvider());
	$app->register(new Silex\Provider\RememberMeServiceProvider());
	$app->register(new Silex\Provider\SessionServiceProvider());
	$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
	$app->register(new Silex\Provider\TwigServiceProvider());
	$app->register(new Silex\Provider\SwiftmailerServiceProvider());



	/* SimpleUser Instance */
	$simpleUserProvider = new SimpleUser\UserServiceProvider();
	$app->register($simpleUserProvider);

	/* oAuth2 Instance*/
	$oAuth = new Perseids\OAuth2();
	$app->register($oAuth);
	require_once __DIR__."/Config/ORM.php";

	/* oAuth2 Instance*/
	$clients = new Perseids\ClientsManager\ClientServiceProvider();
	$app->register($clients);


	/* Debug Mode */
	$app['debug'] = true;

	/* Routes */
	$app->mount('/rest/oauth2', $oAuth);
	$app->mount('/clients', $clients);
	$app->mount('/user', $simpleUserProvider);

	$app->get('/', function () use ($app) {
	    return $app['twig']->render('index.twig', array());
	});


	/* TWIG Configuration */
	$app['twig.path'] = array(__DIR__.'/../templates');
	$app['twig.options'] = array('cache' => __DIR__.'/../var/cache/twig');
	#$app['twig']->addGlobal('layout', $app['twig']->loadTemplate('layout.twig'));

	/* DATABASE CONFIGURATOR */


	//Security Encoder for it
	/*
	$app['security.encoder.digest'] = $app->share(function ($app) {
		return new PlaintextPasswordEncoder();
	});
	*/
	// Mailer config. See http://silex.sensiolabs.org/doc/providers/swiftmailer.html
	$app['swiftmailer.options'] = array();

	/* Firewall configuration */
	$app['security.firewalls'] = array(
		/*
		// Ensure that the login page is accessible to all
		'login' => array(
		'pattern' => '^/user/login$',
		),*/
		'secured_area' => array(
			'pattern' => '^.*$',
			'anonymous' => true,
			'remember_me' => array(),
			'form' => array(
				'login_path' => '/user/login',
				'check_path' => '/user/login_check',
			),
			'logout' => array(
				'logout_path' => '/user/logout',
			),


			'users' => $app->share(function($app) { return $app['user.manager']; }),
		),
		'oauth2_authorize' => array(
			'pattern' => '^/rest/oauth2/authorize$',
			'http' => true,
			'users' => $app->share(function($app) { return $app['user.manager']; }), #We reuse the stuff from SimpleUser
		),
		'oauth2_token' => array(
	        'pattern' => '^/rest/oauth2/token$',
	        'oauth2_token' => true,
	    ),
	    'oauth2_debug' => array(
			'pattern' => '^/rest/oauth2/debug$',
			'oauth2_resource' => true,
		),
	);

	$app['security.access_rules'] = array(
	    array('^/clients', 'ROLE_ADMIN', 'http')/*,
	    array('^.*$', 'ROLE_USER'),*/
	);

	$app['user.passwordStrengthValidator'] = $app->protect(function(SimpleUser\User $user, $password) {
		if (strlen($password) < 4) {
			return 'Password must be at least 4 characters long.';
		}
		if (strtolower($password) == strtolower($user->getName())) {
			return 'Your password cannot be the same as your name.';
		}
	});


	$app['user.options'] = array(
    	// Specify custom view templates here.
    	'templates' => array(
    		//Setting the General Layout
    		'layout' => "layout.twig"
    	)
    );

	return $app;
?>