<?php

namespace App\Controllers;

use App\Models\Despesa;
use App\Models\Saldo;
use App\Middleware\Auth;
use App\Models\Usuario;

class DespesaController {
    public function create() {
        // Verifica autenticação
        Auth::verificar();

        $errors = [];
        $successMessage = '';
        $data = ['nome' => '', 'valor' => '', 'data' => ''];

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Gerar token CSRF se não existir
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrfToken = $_SESSION['csrf_token'];

        // Dados do usuário logado
        $userId = $_SESSION['user_id'];
        $userNome = $_SESSION['user_nome'];

        $usuarioModel = new Usuario();
        $usuarioDados = $usuarioModel->buscarPorId((string)$userId);
        $userApiKey = $usuarioDados['api_key'] ?? null;
        $userEmail = $usuarioDados['email'] ?? '';

        $model = new Despesa($userId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tokenRecebido = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $tokenRecebido)) {
                $errors[] = 'Token de segurança inválido. Recarregue a página e tente novamente.';
            }

            $action = $_POST['action'] ?? 'criar';

            if (empty($errors) && $action === 'remover') {
                $despesaId = trim($_POST['despesa_id'] ?? '');

                if ($despesaId === '') {
                    $errors[] = 'identificador da transação inválido';
                } else {
                    $removido = false;
                    if (str_starts_with($despesaId, 'saldo_')) {
                        $saldoModel = new Saldo($userId);
                        $removido = $saldoModel->removerSaldo($despesaId);
                    } else {
                        $removido = $model->removerDespesa($despesaId);
                    }

                    if ($removido) {
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        header('Location: index.php?route=dashboard#');
                        exit;
                    } else {
                        $errors[] = 'transação não encontrada para remoção';
                    }
                }
            }

            if (empty($errors) && $action === 'salvar_transacao') {
                $tipo = $_POST['tipo'] ?? 'saida';
                $dataArr['nome'] = substr(trim($_POST['nome'] ?? ''), 0, 30);
                $dataArr['descricao'] = substr(trim($_POST['descricao'] ?? ''), 0, 150);
                $dataArr['valor'] = str_replace(',', '.', trim($_POST['valor'] ?? ''));
                if (is_numeric($dataArr['valor']) && (float)$dataArr['valor'] > 99999999.99) $dataArr['valor'] = '99999999.99';
                $dataArr['data'] = trim($_POST['data'] ?? '');
                $dataArr['icone'] = $_POST['icone'] ?? ($tipo === 'entrada' ? '💵' : '📄');
                $dataArr['comprovante'] = $this->handleUpload();
                $recorrenteMensal = ($_POST['recorrente_mensal'] ?? '') === '1';
                $recorrenciaMeses = $recorrenteMensal ? $this->clampRecorrenciaMeses($_POST['recorrencia_meses'] ?? null) : 1;

                if ($dataArr['nome'] === '') {
                    $errors[] = 'informe um título válido';
                }

                if (!is_numeric($dataArr['valor']) || (float)$dataArr['valor'] <= 0) {
                    $errors[] = 'O valor é inválido';
                }

                $date = \DateTime::createFromFormat('Y-m-d', $dataArr['data']);
                $dateErrors = \DateTime::getLastErrors();

                if ($dataArr['data'] === '' || !$date || ($dateErrors && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
                    $errors[] = 'informe uma data válida';
                } else {
                    $dataArr['data'] = $date->format('Y-m-d');
                }

                if (empty($errors)) {
                    $datasRecorrentes = $this->buildMonthlyRecurrenceDates($dataArr['data'], $recorrenciaMeses);
                    $salvouTudo = true;

                    if ($tipo === 'entrada') {
                        $saldoModel = new Saldo($userId);
                        foreach ($datasRecorrentes as $dataRecorrente) {
                            $salvou = $saldoModel->adicionarSaldo(
                                (float)$dataArr['valor'],
                                $dataArr['nome'],
                                $dataRecorrente,
                                $dataArr['descricao'] ?: null,
                                $dataArr['comprovante'],
                                $dataArr['icone']
                            );
                            if (!$salvou) {
                                $salvouTudo = false;
                                break;
                            }
                        }
                    } else {
                        foreach ($datasRecorrentes as $dataRecorrente) {
                            $novaDespesa = $dataArr;
                            $novaDespesa['data'] = $dataRecorrente;
                            $salvou = $model->salvarDespesa($novaDespesa);
                            if (!$salvou) {
                                $salvouTudo = false;
                                break;
                            }
                        }
                    }

                    if (!$salvouTudo) {
                        $errors[] = 'não foi possível salvar todas as transações recorrentes';
                    } else {
                        $totalGerado = count($datasRecorrentes);
                        $_SESSION['successMessage'] = $totalGerado > 1
                            ? "{$totalGerado} transações mensais foram geradas com sucesso"
                            : 'transação cadastrada com sucesso';
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        header('Location: index.php?route=dashboard#');
                        exit;
                    }
                }
            }

            if (empty($errors) && $action === 'editar_transacao') {
                $id = $_POST['transacao_id'] ?? '';
                $isSaldo = str_starts_with($id, 'saldo_');
                $modelAtual = $isSaldo ? new Saldo($userId) : $model;
                
                $dataArr['nome'] = substr(trim($_POST['nome'] ?? ''), 0, 30);
                $dataArr['descricao'] = substr(trim($_POST['descricao'] ?? ''), 0, 150);
                $dataArr['valor'] = str_replace(',', '.', trim($_POST['valor'] ?? ''));
                if (is_numeric($dataArr['valor']) && (float)$dataArr['valor'] > 99999999.99) $dataArr['valor'] = '99999999.99';
                $dataArr['data'] = trim($_POST['data'] ?? '');
                $dataArr['icone'] = $_POST['icone'] ?? ($isSaldo ? '💵' : '📄');
                
                $novoComprovante = $this->handleUpload();
                if ($novoComprovante !== null) {
                    $dataArr['comprovante'] = $novoComprovante;
                }

                if ($dataArr['nome'] === '') {
                    $errors[] = 'informe um título válido';
                }

                if (!is_numeric($dataArr['valor']) || (float)$dataArr['valor'] <= 0) {
                    $errors[] = 'O valor é inválido';
                }

                $date = \DateTime::createFromFormat('Y-m-d', $dataArr['data']);
                $dateErrors = \DateTime::getLastErrors();

                if ($dataArr['data'] === '' || !$date || ($dateErrors && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
                    $errors[] = 'informe uma data válida';
                } else {
                    $dataArr['data'] = $date->format('Y-m-d');
                }

                if (empty($errors)) {
                    $salvou = $isSaldo ? $modelAtual->editarSaldo($id, $dataArr) : $modelAtual->editarDespesa($id, $dataArr);
                    if ($salvou) {
                        $_SESSION['successMessage'] = 'transação editada com sucesso';
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        header('Location: index.php?route=dashboard#');
                        exit;
                    }
                    $errors[] = 'não foi possível salvar as alterações';
                }
            }
        }

        if (!empty($_SESSION['successMessage'])) {
            $successMessage = $_SESSION['successMessage'];
            unset($_SESSION['successMessage']);
        }

        $listaDespesas = $model->buscarDespesas();
        $historicoDespesas = $model->buscarHistoricoCompleto();

        // Calcula o total das despesas exibidas (preparado para filtros futuros)
        $totalDespesas = array_sum(array_column($listaDespesas, 'valor'));

        // Saldo do usuário
        $saldoModel = new Saldo($userId);
        $saldoTotalEntradas = $saldoModel->totalSaldo();
        $saldoDisponivel = $saldoModel->saldoDisponivel();
        $historicoSaldo = $saldoModel->buscarHistorico();

        $todasTransacoes = [];
        foreach ($listaDespesas as $d) {
            $d['tipo'] = 'saida';
            $todasTransacoes[] = $d;
        }
        foreach ($historicoSaldo as $s) {
            $todasTransacoes[] = [
                'id' => $s['id'],
                'nome' => $s['nome'],
                'descricao' => $s['descricao'],
                'valor' => $s['valor'],
                'data' => $s['data'] ?? substr($s['criado_em'], 0, 10),
                'comprovante' => $s['comprovante'],
                'icone' => $s['icone'],
                'criado_em' => $s['criado_em'],
                'tipo' => 'entrada'
            ];
        }

        usort($todasTransacoes, function($a, $b) {
            $dateA = $a['data'] . ' ' . (isset($a['criado_em']) ? substr($a['criado_em'], 11) : '00:00:00');
            $dateB = $b['data'] . ' ' . (isset($b['criado_em']) ? substr($b['criado_em'], 11) : '00:00:00');
            return strcmp($dateB, $dateA);
        });

        // Junta histórico de despesas com histórico completo de saldos
        $historicoSaldosCompleto = $saldoModel->buscarHistoricoCompleto();
        $historicoCompleto = array_merge($historicoDespesas, $historicoSaldosCompleto);
        
        usort($historicoCompleto, function($a, $b) {
            $dateA = $a['deletado_em'] ?? $a['criado_em'] ?? '0000-00-00';
            $dateB = $b['deletado_em'] ?? $b['criado_em'] ?? '0000-00-00';
            return strcmp($dateB, $dateA);
        });

        require_once __DIR__ . '/../Views/despesa_form.php';
    }

    public function editar() {
        Auth::verificar();

        $errors = [];
        $successMessage = '';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrfToken = $_SESSION['csrf_token'];

        $userId = $_SESSION['user_id'];

        $model = new Despesa($userId);

        $id = $_GET['id'] ?? '';
        
        $isSaldo = str_starts_with($id, 'saldo_');
        $modelAtual = $isSaldo ? new Saldo($userId) : $model;
        
        $despesa = $modelAtual->buscarPorId($id);

        if (!$despesa) {
            header('Location: index.php?route=dashboard#');
            exit;
        }

        $data = [
            'nome' => $despesa['nome'],
            'descricao' => $despesa['descricao'] ?? '',
            'valor' => $despesa['valor'],
            'data' => $despesa['data'],
            'icone' => $despesa['icone'] ?? ($isSaldo ? '💵' : '📄'),
            'comprovante' => $despesa['comprovante'] ?? null,
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tokenRecebido = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $tokenRecebido)) {
                $errors[] = 'Token de segurança inválido. Recarregue a página e tente novamente.';
            }

            if (empty($errors)) {
                $data['nome'] = substr(trim($_POST['nome'] ?? ''), 0, 30);
                $data['descricao'] = substr(trim($_POST['descricao'] ?? ''), 0, 150);
                $data['valor'] = str_replace(',', '.', trim($_POST['valor'] ?? ''));
                if (is_numeric($data['valor']) && (float)$data['valor'] > 99999999.99) $data['valor'] = '99999999.99';
                $data['data'] = trim($_POST['data'] ?? '');
                $data['icone'] = $_POST['icone'] ?? ($isSaldo ? '💵' : '📄');
                
                $novoComprovante = $this->handleUpload();
                if ($novoComprovante !== null) {
                    $data['comprovante'] = $novoComprovante;
                }

                if ($data['nome'] === '') {
                    $errors[] = 'informe o nome da despesa';
                }

                if (!is_numeric($data['valor']) || (float)$data['valor'] <= 0) {
                    $errors[] = 'valor inválido';
                }

                $date = \DateTime::createFromFormat('Y-m-d', $data['data']);
                $dateErrors = \DateTime::getLastErrors();

                if ($data['data'] === '' || !$date || ($dateErrors && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
                    $errors[] = 'informe uma data válida';
                } else {
                    $data['data'] = $date->format('Y-m-d');
                }

                if (empty($errors)) {
                    $salvou = $isSaldo ? $modelAtual->editarSaldo($id, $data) : $modelAtual->editarDespesa($id, $data);
                    if ($salvou) {
                        $_SESSION['successMessage'] = 'transação editada com sucesso';
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        header('Location: index.php?route=dashboard#');
                        exit;
                    }

                    $errors[] = 'não foi possível salvar as alterações';
                }
            }
        }

        require_once __DIR__ . '/../Views/editar_despesa.php';
    }

    private function handleUpload(): ?string {
        if (isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['comprovante']['name'], PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $filename = uniqid('comp_') . '.pdf';
                $dest = __DIR__ . '/../../public/uploads/' . $filename;
                if (move_uploaded_file($_FILES['comprovante']['tmp_name'], $dest)) {
                    return 'uploads/' . $filename;
                }
            }
        }
        return null;
    }

    private function clampRecorrenciaMeses(?string $meses): int {
        $valor = (int)($meses ?? 1);
        if ($valor < 1) {
            return 1;
        }
        if ($valor > 24) {
            return 24;
        }
        return $valor;
    }

    private function buildMonthlyRecurrenceDates(string $startDate, int $meses): array {
        $base = \DateTimeImmutable::createFromFormat('Y-m-d', $startDate);
        if (!$base) {
            return [$startDate];
        }

        $diaOriginal = (int)$base->format('d');
        $datas = [];

        for ($i = 0; $i < $meses; $i++) {
            $mesBase = $base
                ->modify('first day of this month')
                ->modify("+{$i} month");
            $ultimoDiaMes = (int)$mesBase->format('t');
            $diaAjustado = min($diaOriginal, $ultimoDiaMes);

            $datas[] = $mesBase
                ->setDate((int)$mesBase->format('Y'), (int)$mesBase->format('m'), $diaAjustado)
                ->format('Y-m-d');
        }

        return $datas;
    }

    private function getDespesasFiltradas() {
        Auth::verificar();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $userId = $_SESSION['user_id'];
        $model = new Despesa($userId);
        $listaDespesas = $model->buscarDespesas();

        $mesFiltro = $_GET['mes'] ?? date('Y-m');
        $prioridadeFiltro = $_GET['prioridade'] ?? 'todas';

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

    public function exportarCsv() {
        $despesas = $this->getDespesasFiltradas();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=despesas_' . date('Y-m-d_H-i') . '.csv');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // BOM for excel
        fputcsv($output, ['Data', 'Nome', 'Valor (R$)', 'Prioridade'], ';');

        foreach ($despesas as $d) {
            fputcsv($output, [
                date('d/m/Y', strtotime($d['data'])),
                $d['nome'],
                number_format($d['valor'], 2, ',', '.'),
                ucfirst($d['prioridade'])
            ], ';');
        }
        fclose($output);
        exit;
    }


}