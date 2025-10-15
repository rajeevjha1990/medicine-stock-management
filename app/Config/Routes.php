<?php
use CodeIgniter\Router\RouteCollection;
/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Auth::login');
$routes->post('/auth/dologin', 'Auth::dologin');
$routes->get('/auth/logout', 'Auth::logout');
$routes->get('dashboard', 'Dashboard::index');
$routes->get('medicine', 'Medicine::index');
$routes->get('medicine/medicine_form', 'Medicine::medicine_form');
$routes->get('medicine/medicine_edit_form/(:num)', 'Medicine::medicine_edit_form/$1');
$routes->post('medicine/new_medicine', 'Medicine::new_medicine');
$routes->get('medicine/remove_medicine/(:num)', 'Medicine::remove_medicine/$1');
$routes->get('medicine/purchase_form', 'Medicine::purchase_form');
$routes->post('medicine/purchase_medicine', 'Medicine::purchase_medicine');
$routes->get('medicine/medicine_stock', 'Medicine::medicine_stock');
$routes->get('medicine/medicine_sale', 'Medicine::medicine_sale');
$routes->post('medicine/sale_medicine', 'Medicine::sale_medicine');
$routes->get('medicine/get_stock_history/(:num)', 'Medicine::get_stock_history/$1');
$routes->get('dashboard/historyData', 'Dashboard::historyData');
$routes->get('medicine/change_password_form', 'Medicine::change_password_form');
$routes->post('medicine/change_password', 'Medicine::change_password');
