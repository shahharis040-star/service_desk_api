<?php

//  Definisce tutte i routes chiamando i vari metodi definiti in router.php
//
//  Firma: $router->METHOD(path, Classe, metodo, requiresAuth)

//  Autenticazione  (public)
$router->post('/auth/register', 'AuthController', 'register');
$router->post('/auth/login',    'AuthController', 'login');
$router->get( '/auth/me',       'AuthController', 'me',     true);

//  Users  (protected)
$router->get(   '/users',       'UserController', 'index',   true);
$router->get(   '/users/{id}',  'UserController', 'show',    true);
$router->put(   '/users/{id}',  'UserController', 'update',  true);
$router->delete('/users/{id}',  'UserController', 'destroy', true);

//  Interventi  (protected)
$router->get(   '/interventions',       'InterventionController', 'index',   true);
$router->post(  '/interventions',       'InterventionController', 'store',   true);
$router->get(   '/interventions/{id}',  'InterventionController', 'show',    true);
$router->put(   '/interventions/{id}',  'InterventionController', 'update',  true);
$router->delete('/interventions/{id}',  'InterventionController', 'destroy', true);