<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;

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

	$cart = Cart::getFromSession();
	$cart->getCalculateTotal();

	$order = new Order();
	$order->setData([
		'idcart'=>$cart->getidcart(),
		'idaddress'=>$address->getidaddress(),
		'iduser'=>$user->getiduser(),
		'idstatus'=>OrderStatus::EM_ABERTO,
		'vltotal'=>$cart->getvltotal()
	]);

	$order->save();

	switch ((int)$_POST['payment-method']) {

		case 1:
		header("Location: /ecommerce/views/order/".$order->getidorder()."/pagseguro");
		break;

		case 2:
		header("Location: /ecommerce/views/order/".$order->getidorder()."/paypal");
		break;

	}
	exit;
});

$app->get("/views/order/:idorder/pagseguro", function($idorder){

	User::verifyLogin(false);

	$order = new Order();
	$order->get((int) $idorder);

	$cart = $order->getCart();

	$page = new Page([
		'header'=>false,
		'footer'=>false
	]);
	$page->setTpl("payment-pagseguro",[
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
		'phone'=>[
			'areaCode'=>substr($order->getnrphone(), 0, 2),
			'number'=>substr($order->getnrphone(), 2, strlen($order->getnrphone()))
		]
	]);

});

$app->get("/views/order/:idorder/paypal", function($idorder){

	User::verifyLogin(false);

	$order = new Order();
	$order->get((int)$idorder);

	$cart = $order->getCart();

	$page = new Page([
		'header'=>false,
		'footer'=>false
	]);
	$page->setTpl("payment-paypal", [
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts()
	]);
	
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

	$app->get("/views/order/:idorder", function($idorder){

		User::verifyLogin(false);

		$order = new Order();
		$order->get((int)$idorder);

		//var_dump($order->getValues());die;

		$page = new Page();
		$page->setTpl("payment", [
			'order'=>$order->getValues()
		]);
	});

	$app->get("/res/boleto/:idorder", function($idorder){

		User::verifyLogin(false);

		$order = new Order();
		$order->get((int)$idorder);

		// DADOS DO BOLETO PARA O SEU CLIENTE
		$dias_de_prazo_para_pagamento = 10;
		$taxa_boleto = 5.00;
		$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 

		$valor_cobrado = formatPrice($order->getvltotal()); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
		$valor_cobrado = str_replace(".", "",$valor_cobrado);
		$valor_cobrado = str_replace(",", ".",$valor_cobrado);
		$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');

		$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
		$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
		$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
		$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
		$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
		$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

		// DADOS DO SEU CLIENTE
		$dadosboleto["sacado"] = $order->getdesperson();
		$dadosboleto["endereco1"] = $order->getdesaddress(). " ".$order->getdesdistrict();
		$dadosboleto["endereco2"] = $order->getdescity(). " - ".$order->getdesstate(). " - ".$order->getdescountry(). " ".$order->getdeszipcode();

		// INFORMACOES PARA O CLIENTE
		$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
		$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
		$dadosboleto["demonstrativo3"] = "";
		$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
		$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
		$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@hcode.com.br";
		$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";

		// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
		$dadosboleto["quantidade"] = "";
		$dadosboleto["valor_unitario"] = "";
		$dadosboleto["aceite"] = "";		
		$dadosboleto["especie"] = "R$";
		$dadosboleto["especie_doc"] = "";


		// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


		// DADOS DA SUA CONTA - ITAÚ
		$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
		$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
		$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta

		// DADOS PERSONALIZADOS - ITAÚ
		$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

		// SEUS DADOS
		$dadosboleto["identificacao"] = "Hcode Treinamentos";
		$dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
		$dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
		$dadosboleto["cidade_uf"] = "São Bernardo do Campo - SP";
		$dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";

		// NÃO ALTERAR!
		$path = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR."ecommerce".DIRECTORY_SEPARATOR."res".DIRECTORY_SEPARATOR."boletophp".DIRECTORY_SEPARATOR."include".DIRECTORY_SEPARATOR;

		require_once($path. "funcoes_itau.php");
		require_once($path. "layout_itau.php");

	});

	$app->get("/views/profile/orders", function(){

		User::verifyLogin(false);
		$user = User::getFromSession();

		$page = new Page();
		$page->setTpl("profile-orders", [
			'orders'=>$user->getOrders()
		]);
	});

	$app->get("/views/profile/orders/:idorder", function($idorder){

		User::verifyLogin(false);

		$order = new Order();
		$order->get((int)$idorder);	

		$cart = new Cart();
		$cart->get((int)$order->getidcart());
		$cart->getCalculateTotal();	

		$page = new Page();
		$page->setTpl("profile-orders-detail", [
			'order'=>$order->getValues(),
			'cart'=>$cart->getValues(),
			'products'=>$cart->getProducts()

		]);		
	});

	$app->get("/views/profile/change-password", function(){

		User::verifyLogin(false);

		$page = new Page();
		$page->setTpl("profile-change-password",[
			'changePassError'=>User::getError(),
			'changePassSuccess'=>User::getSuccess()
		]);

	});

	$app->post("/views/profile/change-password", function(){

		User::verifyLogin(false);

		if(!isset($_POST['current_pass']) || $_POST['current_pass'] === ''){
			User::setError("Digite a senha atual.");

			header("Location: /ecommerce/views/profile/change-password");
			exit;
		}

		if(!isset($_POST['new_pass']) || $_POST['new_pass'] === ''){
			User::setError("Digite a nova senha.");

			header("Location: /ecommerce/views/profile/change-password");
			exit;
		}

		if(!isset($_POST['new_pass_confirm']) || $_POST['new_pass_confirm'] === ''){
			User::setError("Confirme a nova senha.");

			header("Location: /ecommerce/views/profile/change-password");
			exit;
		}

		if($_POST['current_pass'] === $_POST['new_pass']){

			User::setError("A sua nova senha deve ser diferente da atual.");

			header("Location: /ecommerce/views/profile/change-password");
			exit;
		}

		$user = User::getFromSession();
		if(!password_verify($_POST['current_pass'], $user->getdespassword())){

			User::setError("A senha esta inválida.");

			header("Location: /ecommerce/views/profile/change-password");
			exit;
		}

		$user->setdespassword($_POST['new_pass']);
		$user->update();

		User::setSuccess("Senha alterada com sucesso!");
		header("Location: /ecommerce/views/profile/change-password");
			exit;
		
	});

 ?>