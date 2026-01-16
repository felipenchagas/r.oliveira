<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

/**
 * Config
 */
$SMTP_HOST = 'mail.smtp2go.com';
$SMTP_USER = 'disparosite@oliveiraalpinismo.com.br';
$SMTP_PASS = '@altura@Novo2';
$SMTP_PORT = 2525;
$TO_EMAIL  = 'felipe@empresarialweb.com.br'; // quem recebe

/**
 * Helpers
 */
function respond_js(string $msg, string $redirectUrl = ''): void {
    $msg = addslashes($msg);
    header('Content-Type: text/html; charset=UTF-8');
    if ($redirectUrl !== '') {
        $redirectUrl = addslashes($redirectUrl);
        echo "<script>alert('{$msg}'); window.location.href='{$redirectUrl}';</script>";
    } else {
        echo "<script>alert('{$msg}'); window.history.back();</script>";
    }
    exit;
}

function safe_post(string $key): string {
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

/**
 * Security + method
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

// Honeypot
if (safe_post('honeypot') !== '') {
    http_response_code(400);
    exit('Erro: Atividade suspeita detectada (SPAM).');
}

/**
 * Data
 */
$nome      = strip_tags(safe_post('nome'));
$email     = filter_var(safe_post('email'), FILTER_SANITIZE_EMAIL) ?: '';
$ddd       = preg_replace('/\D+/', '', safe_post('ddd'));
$telefone  = preg_replace('/\D+/', '', safe_post('telefone'));
$cidade    = strip_tags(safe_post('cidade'));
$estado    = strip_tags(safe_post('estado'));
$descricao_raw = strip_tags(safe_post('descricao'));
$descricao_html = nl2br(htmlspecialchars($descricao_raw, ENT_QUOTES, 'UTF-8'));

// Basic validation
if ($nome === '' || $email === '' || $telefone === '') {
    respond_js('Por favor, preencha todos os campos obrigatórios.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond_js('E-mail inválido. Verifique e tente novamente.');
}

/**
 * Send
 */
$mail = new PHPMailer(true);

try {
    // SMTP
    $mail->isSMTP();
    $mail->Host       = $SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = $SMTP_USER;
    $mail->Password   = $SMTP_PASS;
    $mail->Port       = $SMTP_PORT;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->CharSet    = 'UTF-8';

    // Debug no log do Apache (deixa ligado só pra diagnosticar)
    $mail->SMTPDebug  = 2; // 0=off, 2=client/server
    $mail->Debugoutput = function ($str, $level) {
        error_log("PHPMailer[$level] $str");
    };

    $mail->Timeout = 20;
    $mail->SMTPKeepAlive = false;

    // From/To
    $mail->setFrom($SMTP_USER, 'Site Oliveira Alpinismo');
    $mail->addAddress($TO_EMAIL);

    // Reply-to do cliente
    $mail->addReplyTo($email, $nome);

    // Content
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
    // Vai pro log também
    error_log('PHPMailer error: ' . $mail->ErrorInfo);
    respond_js("A mensagem não pôde ser enviada. Erro: {$mail->ErrorInfo}");
}
