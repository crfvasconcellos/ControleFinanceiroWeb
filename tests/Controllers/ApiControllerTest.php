<?php

namespace App\Controllers {

    class TestableApiController extends ApiController
    {
        public int $responseCode = 0;
        public array $responsePayload = [];

        protected function sendResponse(int $code, array $payload): void
        {
            $this->responseCode    = $code;
            $this->responsePayload = $payload;
        }
    }
}

namespace {

    use PHPUnit\Framework\TestCase;
    use App\Controllers\TestableApiController;
    use App\Models\Usuario;
    use App\Models\Despesa;
    use App\Models\Saldo;
    use App\Config\Database;

    class ApiControllerTest extends TestCase
    {
        private TestableApiController $controller;
        private array $usuario;

        protected function setUp(): void
        {
            $db = Database::getConnection();
            $db->exec("DELETE FROM usuarios WHERE email LIKE '%@apitest.com'");

            $model         = new Usuario();
            $this->usuario = $model->registrar('API Tester', 'tester@apitest.com', 'senha123');

            unset($_SERVER['HTTP_X_API_KEY']);
            $_POST = [];

            $this->controller = new TestableApiController();
        }

        protected function tearDown(): void
        {
            $db = Database::getConnection();
            $db->exec("DELETE FROM usuarios WHERE email LIKE '%@apitest.com'");

            unset($_SERVER['HTTP_X_API_KEY']);
            $_POST = [];
        }

        private function chamarComApiKeyValida(): void
        {
            $_SERVER['HTTP_X_API_KEY'] = $this->usuario['api_key'];
            $this->controller->despesas();
        }

        private function chamarComApiKeyInvalida(): void
        {
            $_SERVER['HTTP_X_API_KEY'] = 'chave-que-nao-existe-no-banco';
            $this->controller->despesas();
        }

        private function chamarSemApiKey(): void
        {
            $_SERVER['HTTP_X_API_KEY'] = '';
            $_POST = [];
            $this->controller->despesas();
        }

        public function testRetorna401_QuandoApiKeyAusente(): void
        {
            $this->chamarSemApiKey();

            $this->assertSame(401, $this->controller->responseCode);
            $this->assertArrayHasKey('erro', $this->controller->responsePayload);
        }

        public function testRetorna403_QuandoApiKeyInvalida(): void
        {
            $this->chamarComApiKeyInvalida();

            $this->assertSame(403, $this->controller->responseCode);
            $this->assertArrayHasKey('erro', $this->controller->responsePayload);
        }

        public function testRetorna200_ComEstruturaMinimaParaApiKeyValida(): void
        {
            $this->chamarComApiKeyValida();

            $this->assertSame(200, $this->controller->responseCode);

            $payload = $this->controller->responsePayload;
            $this->assertArrayHasKey('usuario',    $payload);
            $this->assertArrayHasKey('email',      $payload);
            $this->assertArrayHasKey('saldo',      $payload);
            $this->assertArrayHasKey('transacoes', $payload);
        }

        public function testPayloadContemDadosDoUsuarioCorreto(): void
        {
            $this->chamarComApiKeyValida();

            $payload = $this->controller->responsePayload;
            $this->assertSame('API Tester',         $payload['usuario']);
            $this->assertSame('tester@apitest.com', $payload['email']);
        }

        public function testSaldo_ZeroParaUsuarioSemTransacoes(): void
        {
            $this->chamarComApiKeyValida();

            $saldo = $this->controller->responsePayload['saldo'];
            $this->assertSame(0.0, (float) $saldo['disponivel']);
            $this->assertSame(0.0, (float) $saldo['total_entradas']);
        }

        public function testTransacoes_VaziaParaUsuarioSemRegistros(): void
        {
            $this->chamarComApiKeyValida();

            $this->assertCount(0, $this->controller->responsePayload['transacoes']);
        }

        public function testTransacoes_IncluiDespesaComTipoSaida(): void
        {
            $despesaModel = new Despesa($this->usuario['id']);
            $despesaModel->salvarDespesa([
                'nome'  => 'Aluguel Abril',
                'valor' => 1500.00,
                'data'  => '2026-04-05',
                'icone' => '🏠',
            ]);

            $this->chamarComApiKeyValida();

            $transacoes = $this->controller->responsePayload['transacoes'];
            $this->assertCount(1, $transacoes);
            $this->assertSame('saida',         $transacoes[0]['tipo']);
            $this->assertSame('Aluguel Abril', $transacoes[0]['nome']);
            $this->assertSame(1500.00,         (float) $transacoes[0]['valor']);
        }

        public function testTransacoes_IncluiSaldoComTipoEntrada(): void
        {
            $saldoModel = new Saldo($this->usuario['id']);
            $saldoModel->adicionarSaldo(3000.00, 'Salário Maio', '2026-05-05');

            $this->chamarComApiKeyValida();

            $transacoes = $this->controller->responsePayload['transacoes'];
            $this->assertCount(1, $transacoes);
            $this->assertSame('entrada',      $transacoes[0]['tipo']);
            $this->assertSame('Salário Maio', $transacoes[0]['nome']);
            $this->assertSame(3000.00,        (float) $transacoes[0]['valor']);
        }

        public function testSaldo_DisponivelCalculadoCorretamente(): void
        {
            $saldoModel   = new Saldo($this->usuario['id']);
            $despesaModel = new Despesa($this->usuario['id']);

            $saldoModel->adicionarSaldo(5000.00, 'Salário', '2026-05-01');
            $despesaModel->salvarDespesa([
                'nome'  => 'Aluguel',
                'valor' => 1200.00,
                'data'  => '2026-05-10',
                'icone' => '🏠',
            ]);

            $this->chamarComApiKeyValida();

            $saldo = $this->controller->responsePayload['saldo'];
            $this->assertSame(5000.00, (float) $saldo['total_entradas']);
            $this->assertSame(3800.00, (float) $saldo['disponivel']);
        }

        public function testTransacoes_OrdenadaDoMaisRecenteParaOmaisAntigo(): void
        {
            $saldoModel   = new Saldo($this->usuario['id']);
            $despesaModel = new Despesa($this->usuario['id']);

            $saldoModel->adicionarSaldo(2000.00, 'Salário', '2026-05-01');
            $despesaModel->salvarDespesa([
                'nome'  => 'Conta de Luz',
                'valor' => 200.00,
                'data'  => '2026-05-15',
                'icone' => '💡',
            ]);
            $despesaModel->salvarDespesa([
                'nome'  => 'Internet',
                'valor' => 100.00,
                'data'  => '2026-04-10',
                'icone' => '🌐',
            ]);

            $this->chamarComApiKeyValida();

            $transacoes = $this->controller->responsePayload['transacoes'];
            $this->assertCount(3, $transacoes);
            $this->assertSame('2026-05-15', $transacoes[0]['data']);
            $this->assertSame('2026-04-10', $transacoes[2]['data']);
        }

        public function testAutenticacao_ViaPostApiKeyValida(): void
        {
            unset($_SERVER['HTTP_X_API_KEY']);
            $_POST = ['api_key' => $this->usuario['api_key']];

            $this->controller->despesas();

            $this->assertSame(200, $this->controller->responseCode);
        }

        public function testAutenticacao_ViaPostApiKeyInvalida(): void
        {
            unset($_SERVER['HTTP_X_API_KEY']);
            $_POST = ['api_key' => 'chave-invalida-via-post'];

            $this->controller->despesas();

            $this->assertSame(403, $this->controller->responseCode);
        }

        public function testTransacoes_IsoladasPorUsuario(): void
        {
            $model2   = new Usuario();
            $usuario2 = $model2->registrar('Outro User', 'outro@apitest.com', 'senha456');

            $despesaModel2 = new Despesa($usuario2['id']);
            $despesaModel2->salvarDespesa([
                'nome'  => 'Despesa do Outro',
                'valor' => 999.00,
                'data'  => '2026-05-20',
                'icone' => '💸',
            ]);

            $this->chamarComApiKeyValida();

            $transacoes = $this->controller->responsePayload['transacoes'];
            $nomes      = array_column($transacoes, 'nome');

            $this->assertNotContains('Despesa do Outro', $nomes);
            $this->assertCount(0, $transacoes);
        }
    }
}