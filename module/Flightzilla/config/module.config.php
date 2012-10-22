<?php
return array(
    'router' => array(
        'routes' => array(
            'home' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        'controller' => 'index',
                        'action'     => 'index',
                    ),
                ),
            ),
            'login' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/login',
                    'defaults' => array(
                        'controller' => 'index',
                        'action'     => 'login',
                    ),
                ),
            ),
            'flightzilla' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/flightzilla[/:controller[/:action]]',
                    'constraints' => array(
                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ),
                    'defaults' => array(
                        'controller' => 'index',
                        'action'     => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type' => 'Wildcard',
                    ),
                ),
            ),
        ),
    ),
    'service_manager' => array(
        'factories' => array(
            '_session' => function(\Zend\ServiceManager\ServiceLocatorInterface $oServiceManager) {
                return new Zend\Session\Container('flightzilla');
            },
            '_log' => function(\Zend\ServiceManager\ServiceLocatorInterface $oServiceManager) {
                $oLogger = new Zend\Log\Logger;
                $oLogger->addWriter(new Zend\Log\Writer\Stream(sprintf('./data/log/%s-error.log', date('Y-m-d'))));

                return $oLogger;
            },
            '_cache' => function(\Zend\ServiceManager\ServiceLocatorInterface $oServiceManager) {
                $sAdapter = (extension_loaded('memcached') === true) ? 'memcached' : '\Flightzilla\Cache\Storage\Adapter\Memcache';
                return Zend\Cache\StorageFactory::factory(array(
                    'adapter' => $sAdapter,
                    'plugins' => array(
                        'serializer'
                    )
                ));
            },
            '_serviceConfig'=> function(\Zend\ServiceManager\ServiceLocatorInterface $oServiceManager) {
                $aConfig = $oServiceManager->get('config');
                $aConfig = $aConfig['flightzilla'];
                return new \Zend\Config\Config($aConfig);
            },
            '_bugzilla' => function(\Zend\ServiceManager\ServiceLocatorInterface $oServiceManager) {
                $oResource = new \Flightzilla\Model\Resource\Manager;
                $oHttpClient = new \Zend\Http\Client();
                $oConfig = $oServiceManager->get('_serviceConfig');
                $oSession = $oServiceManager->get('session');

                $oTicketSource = new \Flightzilla\Model\Ticket\Source\Bugzilla($oResource, $oHttpClient, $oConfig);
                $oTicketSource->setCache($oServiceManager->get('_cache'))
                              ->setAuth($oServiceManager->get('_auth'))
                              ->setProject($oSession->sCurrentProduct);

                return $oTicketSource;
            }
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'index' => 'Flightzilla\Controller\IndexController',
            'kanban' => 'Flightzilla\Controller\KanbanController',
            'planning' => 'Flightzilla\Controller\PlanningController',
            'mergy' => 'Flightzilla\Controller\MergyController',
            'ticket' => 'Flightzilla\Controller\TicketController',
            'analytics' => 'Flightzilla\Controller\AnalyticsController',
        ),
    ),
    'controller_plugins' => array(
        'invokables' => array(
            'authenticate' => 'Flightzilla\Controller\Plugin\Authenticate',
            'ticketservice' => 'Flightzilla\Controller\Plugin\TicketService',
        ),
    ),
    'view_helpers' => array(
        'invokables' => array(
            'buggradient' => 'Flightzilla\View\Helper\Buggradient',
            'deadlinestatus' => 'Flightzilla\View\Helper\Deadlinestatus',
            'estimation' => 'Flightzilla\View\Helper\Estimation',
            'prioritycolor' => 'Flightzilla\View\Helper\Prioritycolor',
            'ticketicons' => 'Flightzilla\View\Helper\Ticketicons',
            'collectiontime' => 'Flightzilla\View\Helper\CollectionTime',
        ),
        'factories' => array(
            'workflow' => function (\Zend\ServiceManager\ServiceLocatorInterface $oServiceManager) {

                return new \Flightzilla\View\Helper\Workflow($oServiceManager->getServiceLocator()->get('_serviceConfig'));
            },
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'flightzilla/index/index' => __DIR__ . '/../view/flightzilla/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);
