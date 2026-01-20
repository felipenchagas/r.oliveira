<?php
// Ativa exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Previne saída de espaços em branco
ob_start();

declare(strict_types=1);

/**
 * --- DIAGNÓSTICO DE ARQUIVOS ---
 * Isso vai impedir o Erro 500 se a pasta vendor não for encontrada
 */
$caminho_vendor_local = __DIR__ . '/vendor/autoload.php';
$caminho_vendor_acima = __DIR__ . '/../vendor/autoload.php';

if (file_exists($caminho_vendor_local)) {
    require $caminho_vendor_local;
} elseif (file_exists($caminho_vendor_acima)) {
    require $caminho_vendor_acima;
} else {
    // Se cair aqui, o erro aparecerá na tela!
    die("<div style='color:red; font-size:20px; padding:20px; border:2px solid red;'>
        <strong>ERRO CRÍTICO:</strong> O arquivo 'vendor/autoload.php' não foi encontrado.<br>
        O PHP procurou em:<br>
        1. $caminho_vendor_local<br>
        2. $caminho_vendor_acima<br><br>
        Verifique se você enviou a pasta 'vendor' para o servidor via FTP.
    </div>");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Configurações
 */
$SMTP_HOST = 'mail.smtp2go.com';
$SMTP_USER = 'disparosite@oliveiraalpinismo.com.br';
$SMTP_PASS = '@altura@Novo2'; // Troque se já tiver criado a nova senha
$SMTP_PORT = 2525;
$TO_EMAIL  = 'comercial@oliveiraalpinismo.com.br';

/**
 * Funções Auxiliares
 */
function respond_js(string $msg, string $redirectUrl = ''): void {
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
 * Segurança
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

if (safe_post('honeypot') !== '') {
    http_response_code(400);
    exit('Erro: SPAM detectado.');
}

/**
 * Coleta de Dados
 */
$nome           = strip_tags(safe_post('nome'));
$email          = filter_var(safe_post('email'), FILTER_SANITIZE_EMAIL) ?: '';
$ddd            = preg_replace('/\D+/', '', safe_post('ddd'));
$telefone       = preg_replace('/\D+/', '', safe_post('telefone'));
$cidade         = strip_tags(safe_post('cidade'));
$estado         = strip_tags(safe_post('estado'));
$descricao_raw  = strip_tags(safe_post('descricao'));
$descricao_html = nl2br(htmlspecialchars($descricao_raw, ENT_QUOTES, 'UTF-8'));

if ($nome === '' || $email === '' || $telefone === '') {
    respond_js('Por favor, preencha todos os campos obrigatórios.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond_js('E-mail inválido.');
}

/**
 * Envio
 */
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = $SMTP_USER;
    $mail->Password   = $SMTP_PASS;
    $mail->Port       = $SMTP_PORT;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->CharSet    = 'UTF-8';
    $mail->SMTPDebug  = 0; 
    $mail->Timeout    = 20;

    $mail->setFrom($SMTP_USER, 'Site Oliveira Alpinismo');
    $mail->addAddress($TO_EMAIL);
    $mail->addReplyTo($email, $nome);

    $mail->isHTML(true);
    $assuntoCidade = trim($cidade . ($estado !== '' ? "/$estado" : ''));
    $mail->Subject = "Novo Orçamento: {$nome}" . ($assuntoCidade !== '' ? " - {$assuntoCidade}" : '');

    $telefone_fmt = ($ddd !== '' ? "($ddd) " : '') . $telefone;

    $mail->Body = "
    <div style='font-family: Arial, sans-serif; color: #333;'>
        <h2>Solicitação de Orçamento</h2>
        <p><strong>Cliente:</strong> " . htmlspecialchars($nome) . "</p>
        <p><strong>E-mail:</strong> " . htmlspecialchars($email) . "</p>
        <p><strong>Telefone:</strong> " . htmlspecialchars($telefone_fmt) . "</p>
        <p><strong>Local:</strong> " . htmlspecialchars($assuntoCidade) . "</p>
        <hr>
        <h3>Mensagem:</h3>
        <p>{$descricao_html}</p>
    </div>";

    $mail->AltBody = "Cliente: $nome\nTel: $telefone_fmt\nMsg: $descricao_raw";

    $mail->send();

    respond_js('Sucesso! Entraremos em contato.', 'https://oliveiraalpinismo.com.br/sucesso');

} catch (Exception $e) {
    // Se der erro de SMTP, mostra na tela agora (para debug)
    respond_js("Erro ao enviar: " . $mail->ErrorInfo);
}