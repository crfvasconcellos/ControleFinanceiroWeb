<?php

namespace Tests\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Despesa;
use App\Config\Database;

class DespesaModelTest extends TestCase
{
    private ?string $testUserId;

    protected function setUp(): void
    {
        $this->testUserId = 'test_user_' . uniqid();
    }

    public function testObterTotalMes(): void
    {
        $model = new Despesa($this->testUserId);
        
        $total = $model->obterTotalMes(date('Y-m'));
        $this->assertIsFloat($total);
        $this->assertGreaterThanOrEqual(0, $total);
    }


    public function testObterComparativoMeses(): void
    {
        $model = new Despesa($this->testUserId);
        $comparativo = $model->obterComparativoMeses();

        $this->assertIsArray($comparativo);


        $this->assertArrayHasKey('mes_atual', $comparativo);
        $this->assertArrayHasKey('mes_anterior', $comparativo);
        $this->assertArrayHasKey('total_mes_atual', $comparativo);
        $this->assertArrayHasKey('total_mes_anterior', $comparativo);
        $this->assertArrayHasKey('diferenca', $comparativo);
        $this->assertArrayHasKey('percentual', $comparativo);
        $this->assertArrayHasKey('aumentou', $comparativo);
        $this->assertArrayHasKey('tendencia', $comparativo);

        $this->assertIsString($comparativo['mes_atual']);
        $this->assertIsString($comparativo['mes_anterior']);
        $this->assertIsFloat($comparativo['total_mes_atual']);
        $this->assertIsFloat($comparativo['total_mes_anterior']);
        $this->assertIsFloat($comparativo['diferenca']);
        $this->assertIsFloat($comparativo['percentual']);
        $this->assertIsBool($comparativo['aumentou']);
        $this->assertIsString($comparativo['tendencia']);

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', $comparativo['mes_atual']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', $comparativo['mes_anterior']);


        $this->assertGreaterThanOrEqual(0, $comparativo['total_mes_atual']);
        $this->assertGreaterThanOrEqual(0, $comparativo['total_mes_anterior']);

        $this->assertContains($comparativo['tendencia'], ['aumento', 'reducao', 'estavel']);

        if ($comparativo['tendencia'] === 'aumento') {
            $this->assertGreaterThan(0, $comparativo['diferenca']);
            $this->assertTrue($comparativo['aumentou']);
        } elseif ($comparativo['tendencia'] === 'reducao') {
            $this->assertLessThan(0, $comparativo['diferenca']);
            $this->assertFalse($comparativo['aumentou']);
        } else { 
            $this->assertEquals(0, $comparativo['diferenca']);
            $this->assertFalse($comparativo['aumentou']);
        }
    }

    public function testObterComparativoComUserIdNulo(): void
    {
        $model = new Despesa(null);
        $comparativo = $model->obterComparativoMeses();

        $this->assertIsArray($comparativo);
        $this->assertEmpty($comparativo);
    }

    public function testPercentualVariacaoComparativo(): void
    {
        $model = new Despesa($this->testUserId);
        $comparativo = $model->obterComparativoMeses();


        if ($comparativo['total_mes_anterior'] === 0.0) {
            $this->assertEquals(0, $comparativo['percentual']);
        } else {
            $percentualEsperado = ($comparativo['diferenca'] / $comparativo['total_mes_anterior']) * 100;
            $this->assertEqualsWithDelta($percentualEsperado, $comparativo['percentual'], 0.01);
        }
    }
}
