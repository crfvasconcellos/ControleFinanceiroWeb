<?php

namespace App\Controllers;

use App\Models\Despesa;
use App\Models\Saldo;
use App\Models\DespesaRecorrente;
use App\Middleware\Auth;

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
                $dataArr['nome'] = trim($_POST['nome'] ?? '');
                $dataArr['descricao'] = trim($_POST['descricao'] ?? '');
                $dataArr['valor'] = str_replace(',', '.', trim($_POST['valor'] ?? ''));
                $dataArr['data'] = trim($_POST['data'] ?? '');
                $dataArr['icone'] = $_POST['icone'] ?? ($tipo === 'entrada' ? '💵' : '📄');
                $dataArr['comprovante'] = $this->handleUpload();

                if ($dataArr['nome'] === '') {
                    $errors[] = 'informe um título válido';
                }

                if (!is_numeric($dataArr['valor']) || (float)$dataArr['valor'] <= 0) {
                    $errors[] = 'valor inválido';
                }

                $date = \DateTime::createFromFormat('Y-m-d', $dataArr['data']);
                $dateErrors = \DateTime::getLastErrors();

                if ($dataArr['data'] === '' || !$date || ($dateErrors && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
                    $errors[] = 'informe uma data válida';
                } else {
                    $dataArr['data'] = $date->format('Y-m-d');
                }

                if (empty($errors)) {
                    if ($tipo === 'entrada') {
                        $saldoModel = new Saldo($userId);
                        $saldoModel->adicionarSaldo((float)$dataArr['valor'], $dataArr['nome'], $dataArr['data'], $dataArr['descricao'] ?: null, $dataArr['comprovante'], $dataArr['icone']);
                    } else {
                        $model->salvarDespesa($dataArr);
                    }
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header('Location: index.php?route=dashboard#');
                    exit;
                }
            }

            // Ações de despesas recorrentes
            if (empty($errors) && $action === 'salvar_recorrente') {
                $recModel = new DespesaRecorrente($userId);
                $recData = [
                    'nome' => trim($_POST['nome'] ?? ''),
                    'descricao' => trim($_POST['descricao'] ?? ''),
                    'valor' => str_replace(',', '.', trim($_POST['valor'] ?? '')),
                    'dia_vencimento' => (int)($_POST['dia_vencimento'] ?? 1),
                    'icone' => $_POST['icone'] ?? '🔄',
                    'data_inicio' => trim($_POST['data_inicio'] ?? ''),
                ];

                if ($recData['nome'] === '') $errors[] = 'informe o nome da despesa fixa';
                if (!is_numeric($recData['valor']) || (float)$recData['valor'] <= 0) $errors[] = 'valor inválido';
                if ($recData['dia_vencimento'] < 1 || $recData['dia_vencimento'] > 31) $errors[] = 'dia inválido';

                if (empty($errors)) {
                    $recModel->criar($recData);
                    // Processa imediatamente para gerar despesas pendentes
                    $recModel->processarPendentes();
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header('Location: index.php?route=dashboard#');
                    exit;
                }
            }

            if (empty($errors) && $action === 'desativar_recorrente') {
                $recModel = new DespesaRecorrente($userId);
                $recModel->desativar(trim($_POST['recorrente_id'] ?? ''));
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: index.php?route=dashboard#modalDespesasFixas');
                exit;
            }

            if (empty($errors) && $action === 'reativar_recorrente') {
                $recModel = new DespesaRecorrente($userId);
                $recModel->reativar(trim($_POST['recorrente_id'] ?? ''));
                // Processa para gerar despesas pendentes
                $recModel->processarPendentes();
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: index.php?route=dashboard#modalDespesasFixas');
                exit;
            }

            if (empty($errors) && $action === 'remover_recorrente') {
                $recModel = new DespesaRecorrente($userId);
                $recModel->remover(trim($_POST['recorrente_id'] ?? ''));
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: index.php?route=dashboard#modalDespesasFixas');
                exit;
            }
        }

        if (!empty($_SESSION['successMessage'])) {
            $successMessage = $_SESSION['successMessage'];
            unset($_SESSION['successMessage']);
        }

        // Processa despesas recorrentes pendentes
        $recorrenteModel = new DespesaRecorrente($userId);
        $recorrenteModel->processarPendentes();

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

        // Dados de despesas recorrentes para a view
        $despesasRecorrentes = $recorrenteModel->listarTodas();

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
                $data['nome'] = trim($_POST['nome'] ?? '');
                $data['descricao'] = trim($_POST['descricao'] ?? '');
                $data['valor'] = str_replace(',', '.', trim($_POST['valor'] ?? ''));
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

    public function exportarPdf() {
        $despesas = $this->getDespesasFiltradas();

        require_once __DIR__ . '/../Libraries/fpdf.php';

        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(190, 10, utf8_decode('Relatório de Despesas'), 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetFillColor(59, 130, 246);
        $pdf->SetTextColor(255);
        $pdf->SetDrawColor(30, 64, 175);
        $pdf->SetLineWidth(.3);
        $pdf->SetFont('Arial', 'B', 12);

        $w = array(40, 80, 40, 30);
        $header = array('Data', 'Nome', 'Prioridade', 'Valor (R$)');
        for($i=0;$i<count($header);$i++) {
            $pdf->Cell($w[$i], 7, utf8_decode($header[$i]), 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFillColor(248, 250, 252);
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial', '', 11);

        $fill = false;
        $total = 0;
        foreach($despesas as $row) {
            $pdf->Cell($w[0], 6, date('d/m/Y', strtotime($row['data'])), 'LR', 0, 'C', $fill);
            $pdf->Cell($w[1], 6, utf8_decode($row['nome']), 'LR', 0, 'L', $fill);
            $pdf->Cell($w[2], 6, utf8_decode(ucfirst($row['prioridade'])), 'LR', 0, 'C', $fill);
            $pdf->Cell($w[3], 6, number_format($row['valor'], 2, ',', '.'), 'LR', 0, 'R', $fill);
            $pdf->Ln();
            $fill = !$fill;
            $total += $row['valor'];
        }
        
        $pdf->Cell(array_sum($w), 0, '', 'T');
        $pdf->Ln();
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell($w[0]+$w[1]+$w[2], 8, 'Total', 1, 0, 'R');
        $pdf->Cell($w[3], 8, number_format($total, 2, ',', '.'), 1, 0, 'R');

        $pdf->Output('D', 'despesas_' . date('Y-m-d_H-i') . '.pdf');
        exit;
    }
}