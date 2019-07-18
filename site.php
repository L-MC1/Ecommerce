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

	$address = new Address();
	$cart = Cart::getFromSession();

	if (!isset($_GET['zipcode'])) {
		$_GET['zipcode'] = $cart->getdeszipcode();
	}

	if(isset($_GET['zipcode'])){
		$address->loadFromCEP($_GET['zipcode']);
		$cart->setdeszipcode($_GET['zipcode']);
		$cart->save();
		$cart->getCalculateTotal();
	}

	if (!$address->getdesaddress()) $address->setdesaddress('');
	if (!$address->getdesnumber()) $address->setdesnumber('');
	if (!$address->getdescomplement()) $address->setdescomplement('');
	if (!$address->getdesdistrict()) $address->setdesdistrict('');
	if (!$address->getdescity()) $address->setdescity('');
	if (!$address->getdesstate()) $address->setdesstate('');
	if (!$address->getdescountry()) $address->setdescountry('');
	if (!$address->getdeszipcode()) $address->setdeszipcode('');
	
	$page = new Page();
	$page->setTpl("checkout",[
		'cart'=>$cart->getValues(),
		'address'=>$address->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Address::getMsgError()
	]);
});

$app->post("/views/checkout", function(){

	User::verifyLogin(false);

	if(!isset($_POST['zipcode']) || $_POST['zipcode'] === ''){
		Address::setMsgError("Informa o CEP");
		header("Location: /ecommerce/views/checkout");
		exit;
	}
	if(!isset($_POST['desaddress']) || $_POST['desaddress'] === ''){
		Address::setMsgError("Informa o endereço");
		header("Location: /ecommerce/views/checkout");
		exit;
	}
	if(!isset($_POST['desdistrict']) || $_POST['desdistrict'] === ''){
		Address::setMsgError("Informa o bairro");
		header("Location: /ecommerce/views/checkout");
		exit;
	}
	if(!isset($_POST['descity']) || $_POST['descity'] === ''){
		Address::setMsgError("Informa a cidade");
		header("Location: /ecommerce/views/checkout");
		exit;
	}
	if(!isset($_POST['desstate']) || $_POST['desstate'] === ''){
		Address::setMsgError("Informa o estado");
		header("Location: /ecommerce/views/checkout");
		exit;
	}
	if(!isset($_POST['descountry']) || $_POST['descountry'] === ''){
		Address::setMsgError("Informa o país");
		header("Location: /ecommerce/views/checkout");
		exit;
	}

	$user = User::getFromSession();

	$address = new Address();

	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();

	$address->setData($_POST);
	$address->save();

	header("Location: /ecommerce/views/order");
	exit;
});

$app->get("/views/login", function(){

	$page = new Page();
	$page->setTpl("login",[
		'error'=>User::getError(),
		'errorRegister'=>User::getErrorRegister(),
		'registerValues'=>(isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name'=>'', 'email'=>'','phone'=>'',]
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

//cadastro de usuario

$app->post("/views/register", function(){

	$_SESSION['registerValues'] = $_POST;

	if(!isset($_POST['name']) || $_POST['name'] == ''){
		User::setErrorRegister("Preencha o seu nome.");
		header("Location: /ecommerce/views/login");
		exit;
	}

	$_SESSION['registerValues'] = $_POST;

	if(!isset($_POST['email']) || $_POST['email'] == ''){
		User::setErrorRegister("Preencha o seu e-mail.");
		header("Location: /ecommerce/views/login");
		exit;
	}

	$_SESSION['registerValues'] = $_POST;

	if(!isset($_POST['password']) || $_POST['password'] == ''){
		User::setErrorRegister("Preencha a sua senha.");
		header("Location: /ecommerce/views/login");
		exit;
	}

	if(User::checkLoginExist($_POST['email']) === true){
		User::setErrorRegister("Este endereço de e-mail já esta sendo utilizado por outro usuário.");
		header("Location: /ecommerce/views/login");
		exit;
	}

	$user = new User();
	$user->setData([
		'inadmin'=>0,
		'deslogin'=>$_POST['email'],
		'desperson'=>$_POST['name'],
		'desemail'=>$_POST['email'],
		'despassword'=>$_POST['password'],		
		'nrphone'=>$_POST['phone']
	]);

	$user->save();

	User::login($_POST['email'], $_POST['password']);

	header("Location: /ecommerce/views/checkout");
	exit;
});

// Recuperação de senha

$app->get("/views/forgot", function(){
	$page = new Page();
	$page->setTpl("forgot");
});

$app->post("/views/forgot", function(){
	$user = User::getForgot($_POST["email"], false);
	header("Location: /ecommerce/views/forgot/sent");
	exit;
});
$app->get("/views/forgot/sent", function(){
	$page = new Page();
	$page->setTpl("forgot-sent");
});

$app->get("/views/forgot/reset", function(){

	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new Page();
	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));
});

	$app->post("/views/forgot/reset", function(){
		$forgot = User::validForgotDecrypt($_POST["code"]);

		User::setForgotUsed($user["idrecovery"]);

		$user = new User();
		$user->get((int)$forgot["iduser"]);

		$password = password_hash($_POST["passwrd"], PASSWORD_DEFAULT, [
			"cost"=>12
		]);

		$user->setPassword($password);

		$page = new Page();
		$page->setTpl("forgot-reset-sucess");
	});

	$app->get("/views/profile", function(){

		User::verifyLogin(false);
		$user = User::getFromSession();

		$page = new Page();
		$page->setTpl("profile",[
			'user'=>$user->getValues(),
			'profileMsg'=>User::getSuccess(),
			'profileError'=>User::getError()
		]);
	});

	$app->post("/views/profile", function(){

		User::verifyLogin(false);

		if (!isset($_POST['desperson']) || $_POST['desperson'] === ''){
			User::setError("Preencha o seu nome.");
			header('Location: /ecommerce/views/profile');
			exit;
		}

		if (!isset($_POST['desemail']) || $_POST['desemail'] === ''){
			User::setError("Preencha o seu e-mail.");
			header('Location: /ecommerce/views/profile');
			exit;
		}

		$user = User::getFromSession();

		if ($_POST['desemail'] !== $user->getdesemail()){
			if(User::checkLoginExist($_POST['desemail']) === true){
				User::setError("Este endereço de e-mail já está cadastrado.");
				header('Location: /ecommerce/views/profile');
				exit;
			}
		}

		$_POST['inadmin'] = $user->getinadmin();
		$_POST['despassword'] = $user->getdespassword();
		$_POST['deslogin'] = $_POST['desemail'];
		
		$user->setData($_POST);
		$user->update();

		User::setSuccess("Dados alterados com sucesso!");

		header("Location: /ecommerce/views/profile");
		exit;

	});

 ?>