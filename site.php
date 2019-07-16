<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;

$app->get('/', function() {

	$products = Product::listAll();
    
	$page = new Page();
	$page->setTpl("index", [
		'products'=>Product::checkList($products)
	]);
});

$app->get("/views/categories/:idcategory", function($idcategory){

		$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
				
		$category = new Category();
		$category->get((int)$idcategory);

		$pagination = $category->getProductsPage();

		$pages = [];

		for ($i=1; $i <= $pagination['pages'] ; $i++) { 
			array_push($pages, [
				'link'=>'/views/categories/'.$category->getidcategory().'?page='.$i,
				'page'=>$i
			]);
		}

		$page = new Page();
		$page->setTpl("category",[
			'category'=>$category->getValues(),
			'products'=>$pagination["data"],
			'pages'=>$pages
		]);
	});

$app->get("/res/site/img/products/:desurl", function($desurl){

	$product = new Product();
	$product->getFromURL($desurl);

	$page = new Page();
	$page->setTpl("product-detail",[
		'product'=>$product->getValues(),
		'categories'=>$product->getCategories()
	]);
});

$app->get("/views/cart", function(){

	$cart = Cart::getFromSession();

	$page = new Page();
	$page->setTpl("cart",[
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Cart::getMsgError()
	]);
});

$app->get("/views/cart/:idproduct/add", function($idproduct){

	$product = new Product();
	$product->get((int)$idproduct);

	$cart  =Cart::getFromSession();	

	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

	for ($i =0; $i < $qtd; $i++){
		$cart->addProduct($product);
	}

	header("Location: /ecommerce/views/cart");
	exit;
});

$app->get("/views/cart/:idproduct/minus", function($idproduct){

	$product = new Product();
	$product->get((int)$idproduct);

	$cart  =Cart::getFromSession();
	$cart->removeProduct($product);

	header("Location: /ecommerce/views/cart");
	exit;
});

$app->get("/views/cart/:idproduct/remove", function($idproduct){

	$product = new Product();
	$product->get((int)$idproduct);

	$cart  =Cart::getFromSession();
	$cart->removeProduct($product, true);

	header("Location: /ecommerce/views/cart");
	exit;
});

$app->post("/views/cart/freight", function(){

	$cart = Cart::getFromSession();
	$cart->setFreight($_POST['zipcode']);

	header("Location: /ecommerce/views/cart");
	exit;

});

$app->get("/views/checkout", function(){

	User::verifyLogin(false);

	$cart = Cart::getFromSession();

	$address = new Address();

	$page = new Page();
	$page->setTpl("checkout",[
		'cart'=>$cart->getValues(),
		'address'=>$address->getValues()
	]);
});

$app->get("/views/login", function(){

	$page = new Page();
	$page->setTpl("login",[
		'error'=>User::getError()
	]);
});

$app->post("/views/login", function(){

	try{
		User::login($_POST['login'], $_POST['password']);
	}	catch(Exception $e){
		User::setError($e->getMessage());
	}

	header("Location: /ecommerce/views/checkout");
	exit;

});

$app->get("/views/logout", function(){

	User::logout();

	header("Location: /ecommerce/views/login");
	exit;
});

 ?>