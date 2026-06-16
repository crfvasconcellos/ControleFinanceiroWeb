<?php

namespace App\Controllers {

    class TestableAuthController extends AuthController
    {
        public string $redirectedTo = '';
        public string $renderedView = '';
        public bool $didRedirect = false;

        protected function redirect(string $url): void
        {
            $this->redirectedTo = $url;
            $this->didRedirect  = true;
        }

        protected function renderView(string $path, array $viewData = []): void
        {
            $this->renderedView = basename($path);
        }
    }
}

namespace {

    use PHPUnit\Framework\TestCase;
    use App\Controllers\TestableAuthController;
    use App\Models\Usuario;
    use App\Config\Database;

    class AuthControllerTest extends TestCase
    {
        private TestableAuthController $controller;

        protected function setUp(): void
        {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION = [];
            $_POST    = [];
            $_SERVER['REQUEST_METHOD'] = 'GET';

            $this->controller = new TestableAuthController();

            $db = Database::getConnection();
            $db->exec("DELETE FROM usuarios WHERE email LIKE '%@authtest.com'");
        }

        protected function tearDown(): void
        {
            $_SESSION = [];
            $_POST    = [];

            $db = Database::getConnection();
            $db->exec("DELETE FROM usuarios WHERE email LIKE '%@authtest.com'");
        }

        private function postComCsrfValido(array $campos): string
        {
            $token = bin2hex(random_bytes(16));
            $_SESSION['csrf_token']    = $token;
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = array_merge(['csrf_token' => $token], $campos);
            return $token;
        }

        private function criarUsuario(
            string $nome  = 'Fulano Teste',
            string $email = 'fulano@authtest.com',
            string $senha = 'senha123'
        ): array {
            $model = new Usuario();
            return $model->registrar($nome, $email, $senha);
        }

        public function testLoginGetExibeFormulario(): void
        {
            $this->controller->login();

            $this->assertNotEmpty($_SESSION['csrf_token']);
            $this->assertSame('login.php', $this->controller->renderedView);
            $this->assertFalse($this->controller->didRedirect);
        }

        public function testLoginRejeita_CsrfTokenInvalido(): void
        {
            $_SESSION['csrf_token']    = 'token-correto';
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = [
                'csrf_token' => 'token-errado',
                'email'      => 'qualquer@authtest.com',
                'senha'      => 'qualquer',
            ];

            $this->controller->login();

            $this->assertSame('login.php', $this->controller->renderedView);
            $this->assertFalse($this->controller->didRedirect);
        }

        public function testLoginRejeita_SemEmail(): void
        {
            $this->postComCsrfValido(['email' => '', 'senha' => 'senha123']);

            $this->controller->login();

            $this->assertSame('login.php', $this->controller->renderedView);
            $this->assertFalse($this->controller->didRedirect);
        }

        public function testLoginRejeita_SemSenha(): void
        {
            $this->postComCsrfValido(['email' => 'fulano@authtest.com', 'senha' => '']);

            $this->controller->login();

            $this->assertSame('login.php', $this->controller->renderedView);
            $this->assertFalse($this->controller->didRedirect);
        }

        public function testLoginRejeita_CredenciaisInvalidas(): void
        {
            $this->postComCsrfValido([
                'email' => 'naoexiste@authtest.com',
                'senha' => 'senhaerrada',
            ]);

            $this->controller->login();

            $this->assertSame('login.php', $this->controller->renderedView);
            $this->assertFalse($this->controller->didRedirect);
            $this->assertEmpty($_SESSION['user_id'] ?? '');
        }

        public function testLoginRedireciona_QuandoCredenciaisValidas(): void
        {
            $this->criarUsuario('Joao Auth', 'joao@authtest.com', 'minhasenha');

            $this->postComCsrfValido([
                'email' => 'joao@authtest.com',
                'senha' => 'minhasenha',
            ]);

            $this->controller->login();

            $this->assertTrue($this->controller->didRedirect);
            $this->assertStringContainsString('dashboard', $this->controller->redirectedTo);
        }

        public function testRegistroGetExibeFormulario(): void
        {
            $this->controller->registro();

            $this->assertNotEmpty($_SESSION['csrf_token']);
            $this->assertSame('registro.php', $this->controller->renderedView);
            $this->assertFalse($this->controller->didRedirect);
        }

        public function testRegistroRejeita_SemNome(): void
        {
            $this->postComCsrfValido([
                'nome'            => '',
                'email'           => 'novo@authtest.com',
                'senha'           => 'abc123',
                'confirmar_senha' => 'abc123',
            ]);

            $this->controller->registro();

            $this->assertSame('registro.php', $this->controller->renderedView);
            $this->assertFalse($this->controller->didRedirect);
        }

        public function testRegistroRejeita_EmailFormatoInvalido(): void
        {
            $this->postComCsrfValido([
                'nome'            => 'Teste',
                'email'           => 'nao-e-um-email',
                'senha'           => 'abc123',
                'confirmar_senha' => 'abc123',
            ]);

            $this->controller->registro();

            $this->assertSame('registro.php', $this->controller->renderedView);
            $this->assertFalse($this->controller->didRedirect);
        }

        public function testRegistroRejeita_SenhaCurta(): void
        {
            $this->postComCsrfValido([
                'nome'            => 'Teste',
                'email'           => 'curta@authtest.com',
                'senha'           => '123',
                'confirmar_senha' => '123',
            ]);

            $this->controller->registro();

            $this->assertSame('registro.php', $this->controller->renderedView);
            $this->assertFalse($this->controller->didRedirect);
        }

        public function testRegistroRejeita_SenhasNaoConcidem(): void
        {
            $this->postComCsrfValido([
                'nome'            => 'Teste',
                'email'           => 'diverge@authtest.com',
                'senha'           => 'abc123',
                'confirmar_senha' => 'xyz999',
            ]);

            $this->controller->registro();

            $this->assertSame('registro.php', $this->controller->renderedView);
            $this->assertFalse($this->controller->didRedirect);
        }

        public function testRegistroRejeita_CsrfInvalido(): void
        {
            $_SESSION['csrf_token']    = 'token-correto';
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = [
                'csrf_token'      => 'token-errado',
                'nome'            => 'Teste',
                'email'           => 'csrf@authtest.com',
                'senha'           => 'abc123',
                'confirmar_senha' => 'abc123',
            ];

            $this->controller->registro();

            $this->assertSame('registro.php', $this->controller->renderedView);
            $this->assertFalse($this->controller->didRedirect);
        }

        public function testRegistroRejeita_EmailDuplicado(): void
        {
            $this->criarUsuario('Ja Existe', 'duplicado@authtest.com', 'senha123');

            $this->postComCsrfValido([
                'nome'            => 'Outro Nome',
                'email'           => 'duplicado@authtest.com',
                'senha'           => 'senha456',
                'confirmar_senha' => 'senha456',
            ]);

            $this->controller->registro();

            $this->assertSame('registro.php', $this->controller->renderedView);
            $this->assertFalse($this->controller->didRedirect);
        }

        public function testRegistroCria_UsuarioERedirecionaParaLogin(): void
        {
            $this->postComCsrfValido([
                'nome'            => 'Novo Usuario',
                'email'           => 'novo@authtest.com',
                'senha'           => 'senha123',
                'confirmar_senha' => 'senha123',
            ]);

            $this->controller->registro();

            $this->assertTrue($this->controller->didRedirect);
            $this->assertStringContainsString('login', $this->controller->redirectedTo);
            $this->assertNotEmpty($_SESSION['successMessage'] ?? '');

            $model   = new Usuario();
            $usuario = $model->autenticar('novo@authtest.com', 'senha123');
            $this->assertNotNull($usuario);
        }

        public function testLogoutLimpaSessionERedirecionaParaLogin(): void
        {
            $_SESSION['user_id']    = 'usr_fake';
            $_SESSION['user_nome']  = 'Teste';
            $_SESSION['user_email'] = 'teste@authtest.com';

            $this->controller->logout();

            $this->assertTrue($this->controller->didRedirect);
            $this->assertStringContainsString('login', $this->controller->redirectedTo);
            $this->assertEmpty($_SESSION);
        }
    }
}