<?php

namespace App\Models;

class Despesa {

    private $storageFile = __DIR__ . '/../../data/despesas.json';

    public function buscarDespesas(): array {
        if(!file_exists($this->storageFile)) {
            return [];
        }

        $json = file_get_contents($this->storageFile);
        $data = json_decode($json, true);

        if (!is_array($data)) {
            return [];
        }

        usort($data, fn($a, $b) => strcmp($b['data'], $a['data']));

        return $data;
    }

    public function salvarDespesa(array $data): bool {
        $dir = dirname($this->storageFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $despesas = $this->buscarDespesas();

        $novaDespesa = [
            'id' => uniqid('desp_', true),
            'nome' => $data['nome'],
            'valor' => (float) $data['valor'],
            'data' => $data['data'],
            'criado_em' => date('c'),
        ];

        $despesas[] = $novaDespesa;

        $resultado = file_put_contents(
            $this->storageFile,
            json_encode($despesas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $resultado !== false;
    }
}