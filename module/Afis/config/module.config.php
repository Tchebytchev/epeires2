<?php

namespace Afis;

use Zend\Mvc\Router\Console\Simple;
use Afis\Controller\AfisController;

return [
    'router' => array(
        'routes' => array(
            'afis' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/afis[/:action]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*'
                    ),
                    'defaults' => array(
                        'controller' => AfisController::class,
                        'action' => 'index'
                    )
                )
            )
        )
    ),
    
    /**
     * Doctrine 2 Configuration
     */
    'doctrine' => [
        'driver' => [
            __NAMESPACE__ . '_driver' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [(__DIR__ . '/../src/Entity')]
            ],
            'orm_default' => [
                'drivers' => [
                    __NAMESPACE__ . '\Entity' => __NAMESPACE__ . '_driver'
                ]
            ]
        ]
    ],
    /*
     'doctrine' => array(
        'driver' => array(
            'afis-driver' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(
                    __DIR__ . '/../src/Entity'
                )
            ),
            'orm_default' => array(
                'drivers' => array(
                    'Afis\Entity' => 'afis-driver'
                )
            )
        )
    ),   
    */
    'view_manager' => [
        'template_map' => [
            'afis/layout'             => __DIR__ . '/../../../public/layout/default.phtml',
            'afis/admin/layout'         => __DIR__ . '/../../Administration/view/layout/adminlayout.phtml',
            
            'afis/afis/index'           => __DIR__ . '/../view/afis/index.phtml',
            'afis/afis/admin'           => __DIR__ . '/../view/afis/admin.phtml',
            'afis/form'                 => __DIR__ . '/../view/afis/form.phtml',
            'afis/save'                 => __DIR__ . '/../view/afis/save.phtml',
            
            'afis/helper/afis'          => __DIR__ . '/../view/afis/helpers/afis.phtml',
            'afis/helper/admin'         => __DIR__ . '/../view/afis/helpers/afis-admin.phtml',
            'afis/helper/sidenav'       => __DIR__ . '/../view/afis/helpers/sidenav.phtml',
        ],
    ],
    'asset_manager' => [
        'resolver_configs' => [
            'paths' => [
                __DIR__ . '/../public'
            ]
        ]
    ],
];