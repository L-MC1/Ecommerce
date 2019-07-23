<?php 

//use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;

$app->get("/admin/orders/:idorder/status", function($idorder){

	User::verifyLogin();

	$order = new Order();
	$order->get((int) $idorder);

	$cart = $order->getCart();

	$page = new PageAdmin();
	$page->setTpl("order",[
		'order'=>$order->getValues(),
		'status'=>OrderStatus::listAll(),
		'msgSuccess'=>Order::getSuccess(),
		'msgError'=>Order::getError()
	]);

});

$app->get("/admin/orders/:idorder/delete", function($idorder){

	User::verifyLogin();

	$order = new Order();
	$order->get((int) $idorder);
	$order->delete();

	header("Location: /ecommerce/admin/orders");
	exit;

});

$app->get("/admin/orders/:idorder", function($idorder){

	User::verifyLogin();

	$order = new Order();
	$order->get((int) $idorder);

	$cart = $order->getCart();

	$page = new PageAdmin();
	$page->setTpl("order",[
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts()
	]);

});

$app->get("/admin/orders", function(){

	User::verifyLogin();

	$page = new PageAdmin();
	$page->setTpl("orders", [
		'orders'=>Order::listAll()
	]);

});

 ?>