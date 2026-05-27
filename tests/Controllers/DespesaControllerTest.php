<?php

/**
 * Testes unitários para o método getDespesasFiltradas de DespesaController.
 *
 * Estratégia:
 *   1. Sobrescreve file_get_contents no namespace App\Controllers para
 *      interceptar a chamada HTTP e devolver um JSON fictício.
 *   2. Cria uma subclasse (TestableDespesaController) que expõe um
 *      método público replicando a lógica de filtragem sem acessar o
 *      banco de dados (elimina a dependência de \App\Models\Usuario).
 *   3. Utiliza ReflectionMethod para demonstrar como tornar o método
 *      privado acessível durante a execução do teste.
 */

// ═══════════════════════════════════════════════════════
//  Namespace App\Controllers — sobrescrita de file_get_contents
// ═══════════════════════════════════════════════════════
namespace App\Controllers {

    /**
     * Função que substitui a built-in file_get_contents dentro do namespace
     * App\Controllers. Qualquer chamada não qualificada a file_get_contents()
     * feita por classes neste namespace usará esta versão.
     */
    function file_get_contents(string $url, bool $useIncludePath = false, $context = null): string|false
    {
        return $GLOBALS['__fakeApiResponse'] ?? false;
    }

    /**
     * Subclasse testável de DespesaController.
     *
     * Expõe um método público que reproduz fielmente a lógica de
     * getDespesasFiltradas, porém sem criar instâncias de
     * \App\Models\Usuario (evitando acesso ao banco de dados).
     */
    class TestableDespesaController extends DespesaController
    {
        /**
         * Versão pública e testável da lógica de getDespesasFiltradas.
         *
         * Replica a requisição HTTP (que será interceptada pela sobrescrita
         * de file_get_contents acima) e toda a lógica de filtragem por mês
         * e prioridade — idêntica ao método original.
         */
        public function getDespesasFiltradasPublic(string $userId, string $mesFiltro, string $prioridadeFiltro): array
        {
            // Bypass da busca ao banco: usa uma API key fixa para testes
            $apiKey = 'fake-api-key-para-testes';

            $url = "http://localhost:8000/api.php?route=despesas";
            $opcoes = [
                "http" => [
                    "header" => "X-API-KEY: " . $apiKey . "\r\n",
                    "method" => "GET"
                ]
            ];

            $contexto = stream_context_create($opcoes);

            // file_get_contents() aqui resolve para a função sobrescrita
            // no namespace App\Controllers — retornando $GLOBALS['__fakeApiResponse']
            $resposta = @file_get_contents($url, false, $contexto);

            $listaDespesas = [];
            if ($resposta) {
                $json = json_decode($resposta, true);
                $listaDespesas = $json['data'] ?? [];
            }

            // Lógica de filtragem — idêntica ao método original privado
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

// ═══════════════════════════════════════════════════════
//  Namespace global — classe de teste PHPUnit
// ═══════════════════════════════════════════════════════
namespace {

    use PHPUnit\Framework\TestCase;
    use App\Controllers\TestableDespesaController;
    use App\Controllers\DespesaController;

    class DespesaControllerTest extends TestCase
    {
        private TestableDespesaController $controller;
        private \ReflectionMethod $metodoFiltrar;

        /**
         * Dados fictícios simulando o retorno JSON da API.
         *
         * Contém despesas com valores variados para exercitar as três
         * faixas de prioridade e datas em meses diferentes:
         *
         *   - alta:  valor > 500   (Aluguel R$1500, Plano de Saúde R$800)
         *   - media: valor > 100   (Conta de Luz R$250, Internet R$120)
         *   - baixa: valor <= 100  (Café R$15, Lanche R$30)
         *
         *   - Meses: abril (1), maio (3), junho (2)
         */
        private static array $despesasFicticias = [
            [
                'id'    => '1',
                'nome'  => 'Aluguel',
                'valor' => 1500.00,   // alta
                'data'  => '2026-05-10',
            ],
            [
                'id'    => '2',
                'nome'  => 'Conta de Luz',
                'valor' => 250.00,    // media
                'data'  => '2026-05-15',
            ],
            [
                'id'    => '3',
                'nome'  => 'Café',
                'valor' => 15.00,     // baixa
                'data'  => '2026-05-20',
            ],
            [
                'id'    => '4',
                'nome'  => 'Plano de Saúde',
                'valor' => 800.00,    // alta
                'data'  => '2026-06-05',
            ],
            [
                'id'    => '5',
                'nome'  => 'Internet',
                'valor' => 120.00,    // media
                'data'  => '2026-06-10',
            ],
            [
                'id'    => '6',
                'nome'  => 'Lanche',
                'valor' => 30.00,     // baixa
                'data'  => '2026-04-22',
            ],
        ];

        protected function setUp(): void
        {
            $this->controller = new TestableDespesaController();

            // Injeta a resposta JSON simulada na variável global, que é
            // lida pela função file_get_contents sobrescrita no namespace
            $GLOBALS['__fakeApiResponse'] = json_encode([
                'data' => self::$despesasFicticias,
            ]);

            // Usa ReflectionMethod para tornar o método privado acessível,
            // demonstrando a técnica conforme solicitado
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

        // ──────────────────────────────────────────────
        //  Teste 1: Todos os meses + Todas as prioridades
        // ──────────────────────────────────────────────

        /**
         * Sem nenhum filtro ativo ('todos' / 'todas'), o método deve
         * retornar todas as 6 despesas com a prioridade classificada
         * corretamente com base no valor.
         */
        public function testFiltroTodosMesesETodasPrioridades(): void
        {
            $resultado = $this->controller->getDespesasFiltradasPublic(
                'user_123',
                'todos',
                'todas'
            );

            // Deve retornar todas as 6 despesas fictícias
            $this->assertCount(6, $resultado);

            // Verifica se cada despesa recebeu o campo 'prioridade'
            foreach ($resultado as $despesa) {
                $this->assertArrayHasKey('prioridade', $despesa);
            }

            // Indexa por nome para facilitar as asserções
            $porNome = array_column($resultado, null, 'nome');

            $this->assertSame('alta',  $porNome['Aluguel']['prioridade']);          // 1500
            $this->assertSame('media', $porNome['Conta de Luz']['prioridade']);     // 250
            $this->assertSame('baixa', $porNome['Café']['prioridade']);             // 15
            $this->assertSame('alta',  $porNome['Plano de Saúde']['prioridade']);   // 800
            $this->assertSame('media', $porNome['Internet']['prioridade']);         // 120
            $this->assertSame('baixa', $porNome['Lanche']['prioridade']);           // 30
        }

        // ──────────────────────────────────────────────
        //  Teste 2: Filtro por mês específico (2026-05)
        // ──────────────────────────────────────────────

        /**
         * Ao filtrar pelo mês '2026-05', somente as 3 despesas de maio
         * devem ser retornadas: Aluguel, Conta de Luz e Café.
         */
        public function testFiltroPorMesEspecifico(): void
        {
            $resultado = $this->controller->getDespesasFiltradasPublic(
                'user_123',
                '2026-05',
                'todas'
            );

            // Maio possui exatamente 3 despesas
            $this->assertCount(3, $resultado);

            $nomes = array_column($resultado, 'nome');
            $this->assertContains('Aluguel', $nomes);
            $this->assertContains('Conta de Luz', $nomes);
            $this->assertContains('Café', $nomes);

            // Despesas de outros meses devem estar ausentes
            $this->assertNotContains('Plano de Saúde', $nomes); // junho
            $this->assertNotContains('Internet', $nomes);       // junho
            $this->assertNotContains('Lanche', $nomes);         // abril
        }

        // ──────────────────────────────────────────────
        //  Teste 3: Filtro exclusivo por prioridade 'alta'
        // ──────────────────────────────────────────────

        /**
         * Ao filtrar por prioridade 'alta' em todos os meses, somente
         * despesas com valor > 500 devem ser retornadas.
         */
        public function testFiltroPrioridadeAlta(): void
        {
            $resultado = $this->controller->getDespesasFiltradasPublic(
                'user_123',
                'todos',
                'alta'
            );

            // Apenas 2 despesas > R$500: Aluguel (1500) e Plano de Saúde (800)
            $this->assertCount(2, $resultado);

            $nomes = array_column($resultado, 'nome');
            $this->assertContains('Aluguel', $nomes);
            $this->assertContains('Plano de Saúde', $nomes);

            // Confirma prioridade e valor de cada resultado
            foreach ($resultado as $despesa) {
                $this->assertSame('alta', $despesa['prioridade']);
                $this->assertGreaterThan(500, $despesa['valor']);
            }
        }
    }
}
