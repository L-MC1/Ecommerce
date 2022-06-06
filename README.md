Em construção...

Projeto - ecommerce
Neste projeto utilizo:

Slim framework - para as rotas
Rain TPL - para construção (renderização) das paginas
PHP Mailer - como serviço smtp para envio de e-mails
Composer - para gerenciamento de pacotes

Banco de dados do projeto se encontra em confDB

Das funções dentro do projeto possui:

area de administração:
- area de cadastro e gerenciamento de novos produtos ou usuarios
- controle de pedidos

area do usuario:
- home page
- carrinho de compras
- pagina de detalhe dos produtos
- calculo de frete (API service externo)
- geração de boleto
- sistema de pagamento (Paypal)
- lista de desejos

Obs: em alguns sistemas é necessario editar o .htacess, e dependendo da hospedagem também é necessario editar a classe Mailer (principalmente no Godaddy) host, porta...

(つ◉益◉)つ ⊂(・﹏・⊂)
