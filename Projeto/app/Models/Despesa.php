<?php

namespace App\Models;

class Despesa {

    private $storageFile = __DIR__ . '/../../data/despesas.json';

    //TODO US02 - Implementar listar despesas
    //public function listarDespesas

    public function buscarDespesas(): array {
        if(!file_exists($this->storageFile)) {
            return [];
        }

        $json = file_get_contents($this->storageFile);
        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }

    public function salvarDespesa(array $data): bool {
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