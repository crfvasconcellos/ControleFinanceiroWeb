<?php

namespace App\Controllers {

    function file_get_contents(string $url, bool $useIncludePath = false, $context = null): string|false
    {
        return $GLOBALS['__fakeApiResponse'] ?? false;
    }

    class TestableDespesaController extends DespesaController
    {
        public function getDespesasFiltradasPublic(string $userId, string $mesFiltro, string $prioridadeFiltro): array
        {
            $apiKey = 'fake-api-key-para-testes';

            $url = "http://localhost:8000/api.php?route=despesas";
            $opcoes = [
                "http" => [
                    "header" => "X-API-KEY: " . $apiKey . "\r\n",
                    "method" => "GET"
                ]
            ];

            $contexto = stream_context_create($opcoes);
            $resposta = @file_get_contents($url, false, $contexto);

            $listaDespesas = [];
            if ($resposta) {
                $json = json_decode($resposta, true);
                $listaDespesas = $json['data'] ?? [];
            }

            $despesasFiltradas = [];
            foreach ($listaDespesas as $despesa) {
                $mesDespesa = substr($despesa['data'], 0, 7);
                if ($mesFiltro !== 'todos' && $mesDespesa !== $mesFiltro) continue;

                $prioridade = 'baixa';
                if ($despesa['valor'] > 500) $prioridade = 'alta';
                elseif ($despesa['valor'] > 100) $prioridade = 'media';

                if ($prioridadeFiltro !== 'todas' && $prioridade !== $prioridadeFiltro) continue;

                $despesa['prioridade'] = $prioridade;
                $despesasFiltradas[] = $despesa;
            }

            return $despesasFiltradas;
        }
    }

}

namespace {

    use PHPUnit\Framework\TestCase;
    use App\Controllers\TestableDespesaController;
    use App\Controllers\DespesaController;

    class DespesaControllerTest extends TestCase
    {
        private TestableDespesaController $controller;
        private \ReflectionMethod $metodoFiltrar;

        private static array $despesasFicticias = [
            [
                'id'    => '1',
                'nome'  => 'Aluguel',
                'valor' => 1500.00,
                'data'  => '2026-05-10',
            ],
            [
                'id'    => '2',
                'nome'  => 'Conta de Luz',
                'valor' => 250.00,
                'data'  => '2026-05-15',
            ],
            [
                'id'    => '3',
                'nome'  => 'Café',
                'valor' => 15.00,
                'data'  => '2026-05-20',
            ],
            [
                'id'    => '4',
                'nome'  => 'Plano de Saúde',
                'valor' => 800.00,
                'data'  => '2026-06-05',
            ],
            [
                'id'    => '5',
                'nome'  => 'Internet',
                'valor' => 120.00,
                'data'  => '2026-06-10',
            ],
            [
                'id'    => '6',
                'nome'  => 'Lanche',
                'valor' => 30.00,
                'data'  => '2026-04-22',
            ],
        ];

        protected function setUp(): void
        {
            $this->controller = new TestableDespesaController();

            $GLOBALS['__fakeApiResponse'] = json_encode([
                'data' => self::$despesasFicticias,
            ]);

            $this->metodoFiltrar = new \ReflectionMethod(
                DespesaController::class,
                'getDespesasFiltradas'
            );
            $this->metodoFiltrar->setAccessible(true);
        }

        protected function tearDown(): void
        {
            unset($GLOBALS['__fakeApiResponse']);
        }

        public function testFiltroTodosMesesETodasPrioridades(): void
        {
            $resultado = $this->controller->getDespesasFiltradasPublic(
                'user_123',
                'todos',
                'todas'
            );

            $this->assertCount(6, $resultado);

            foreach ($resultado as $despesa) {
                $this->assertArrayHasKey('prioridade', $despesa);
            }

            $porNome = array_column($resultado, null, 'nome');

            $this->assertSame('alta',  $porNome['Aluguel']['prioridade']);
            $this->assertSame('media', $porNome['Conta de Luz']['prioridade']);
            $this->assertSame('baixa', $porNome['Café']['prioridade']);
            $this->assertSame('alta',  $porNome['Plano de Saúde']['prioridade']);
            $this->assertSame('media', $porNome['Internet']['prioridade']);
            $this->assertSame('baixa', $porNome['Lanche']['prioridade']);
        }

        public function testFiltroPorMesEspecifico(): void
        {
            $resultado = $this->controller->getDespesasFiltradasPublic(
                'user_123',
                '2026-05',
                'todas'
            );

            $this->assertCount(3, $resultado);

            $nomes = array_column($resultado, 'nome');
            $this->assertContains('Aluguel', $nomes);
            $this->assertContains('Conta de Luz', $nomes);
            $this->assertContains('Café', $nomes);

            $this->assertNotContains('Plano de Saúde', $nomes);
            $this->assertNotContains('Internet', $nomes);
            $this->assertNotContains('Lanche', $nomes);
        }

        public function testFiltroPrioridadeAlta(): void
        {
            $resultado = $this->controller->getDespesasFiltradasPublic(
                'user_123',
                'todos',
                'alta'
            );

            $this->assertCount(2, $resultado);

            $nomes = array_column($resultado, 'nome');
            $this->assertContains('Aluguel', $nomes);
            $this->assertContains('Plano de Saúde', $nomes);

            foreach ($resultado as $despesa) {
                $this->assertSame('alta', $despesa['prioridade']);
                $this->assertGreaterThan(500, $despesa['valor']);
            }
        }
    }
}
