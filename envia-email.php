<?php
// Previne saída de espaços em branco antes dos headers
// --- ADICIONE ESTAS 3 LINHAS PARA VER O ERRO NA TELA ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Certifique-se que o caminho do autoload está correto no seu servidor
require __DIR__ . '/vendor/autoload.php';

/**
 * Configurações
 * IMPORTANTE: Troque a senha abaixo pela nova senha que você criar!
 */
$SMTP_HOST = 'mail.smtp2go.com';
$SMTP_USER = 'disparosite@oliveiraalpinismo.com.br';
$SMTP_PASS = '@altura@Novo2'; // <--- TROQUE ESSA SENHA IMEDIATAMENTE POR SEGURANÇA
$SMTP_PORT = 2525;
$TO_EMAIL  = 'comercial@oliveiraalpinismo.com.br';

/**
 * Funções Auxiliares
 */
function respond_js(string $msg, string $redirectUrl = ''): void {
    // Limpa qualquer output anterior para evitar conflito de JSON/JS
    ob_clean(); 
    
    $msg = addslashes($msg);
    header('Content-Type: text/html; charset=UTF-8');
    
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>";
    if ($redirectUrl !== '') {
        $redirectUrl = addslashes($redirectUrl);
        echo "<script>alert('{$msg}'); window.location.href='{$redirectUrl}';</script>";
    } else {
        echo "<script>alert('{$msg}'); window.history.back();</script>";
    }
    echo "</body></html>";
    exit;
}

function safe_post(string $key): string {
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

/**
 * Segurança e Validação de Método
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Se acessar direto pelo navegador, redireciona para a home
    header('Location: /');
    exit;
}

// Honeypot (Anti-Spam simples)
if (safe_post('honeypot') !== '') {
    http_response_code(400);
    exit('Erro: Atividade suspeita detectada (SPAM).');
}

/**
 * Coleta de Dados
 */
$nome          = strip_tags(safe_post('nome'));
$email         = filter_var(safe_post('email'), FILTER_SANITIZE_EMAIL) ?: '';
$ddd           = preg_replace('/\D+/', '', safe_post('ddd'));
$telefone      = preg_replace('/\D+/', '', safe_post('telefone'));
$cidade        = strip_tags(safe_post('cidade'));
$estado        = strip_tags(safe_post('estado'));
$descricao_raw = strip_tags(safe_post('descricao'));
$descricao_html = nl2br(htmlspecialchars($descricao_raw, ENT_QUOTES, 'UTF-8'));

// Validação básica
if ($nome === '' || $email === '' || $telefone === '') {
    respond_js('Por favor, preencha todos os campos obrigatórios.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond_js('E-mail inválido. Verifique e tente novamente.');
}

/**
 * Envio do E-mail
 */
$mail = new PHPMailer(true);

try {
    // Configuração do Servidor
    $mail->isSMTP();
    $mail->Host       = $SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = $SMTP_USER;
    $mail->Password   = $SMTP_PASS;
    $mail->Port       = $SMTP_PORT;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Tente ENCRYPTION_SMTPS se falhar com porta 465
    $mail->CharSet    = 'UTF-8';

    // Debug: Mantenha desligado (0) em produção para não quebrar o JS de resposta
    // Use o log do servidor para ver erros
    $mail->SMTPDebug  = 0; 

    $mail->Timeout = 20;
    $mail->SMTPKeepAlive = false;

    // Remetente e Destinatário
    $mail->setFrom($SMTP_USER, 'Site Oliveira Alpinismo');
    $mail->addAddress($TO_EMAIL);

    // Reply-to (Responder para o cliente)
    $mail->addReplyTo($email, $nome);

    // Conteúdo
    $mail->isHTML(true);
    $assuntoCidade = trim($cidade . ($estado !== '' ? "/$estado" : ''));
    $mail->Subject = "Novo Orçamento: {$nome}" . ($assuntoCidade !== '' ? " - {$assuntoCidade}" : '');

    $telefone_fmt = ($ddd !== '' ? "($ddd) " : '') . $telefone;

    $mail->Body = "
    <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.4;'>
        <h2>Nova Solicitação de Orçamento</h2>
        <p><strong>Cliente:</strong> " . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . "</p>
        <p><strong>E-mail:</strong> " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</p>
        <p><strong>Telefone:</strong> " . htmlspecialchars($telefone_fmt, ENT_QUOTES, 'UTF-8') . "</p>
        <p><strong>Local:</strong> " . htmlspecialchars($assuntoCidade, ENT_QUOTES, 'UTF-8') . "</p>
        <hr>
        <h3>Descrição do Serviço:</h3>
        <div style='background:#f4f4f4;padding:15px;border-radius:6px;'>
            {$descricao_html}
        </div>
        <p style='margin-top:12px;'><small>Enviado através do formulário do site.</small></p>
    </div>
    ";

    $mail->AltBody =
        "Novo Orçamento\n\n" .
        "Nome: {$nome}\n" .
        "Email: {$email}\n" .
        "Tel: {$telefone_fmt}\n" .
        "Cidade: {$assuntoCidade}\n\n" .
        "Descrição:\n{$descricao_raw}\n";

    $mail->send();

    respond_js('Orçamento solicitado com sucesso! Entraremos em contato em breve.', 'https://oliveiraalpinismo.com.br/sucesso');

} catch (Exception $e) {
    // Registra o erro no log do servidor (arquivo error_log)
    error_log('PHPMailer error: ' . $mail->ErrorInfo);
    respond_js("A mensagem não pôde ser enviada. Tente novamente mais tarde.");
}