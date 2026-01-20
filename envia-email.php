<?php
// --- SISTEMA DE LOG DE ERROS ---
// Isso vai criar um arquivo erro_log.txt na pasta para você ler o erro real
$arquivoLog = __DIR__ . '/erro_log.txt';

function gravarLog($msg) {
    global $arquivoLog;
    $data = date('d/m/Y H:i:s');
    // Grava no arquivo
    file_put_contents($arquivoLog, "[$data] $msg" . PHP_EOL, FILE_APPEND);
}

// Captura erros fatais (Tela Branca / Erro 500 que o PHP esconde)
register_shutdown_function(function() {
    $erro = error_get_last();
    if ($erro !== null) {
        gravarLog("ERRO CRÍTICO (SHUTDOWN): " . $erro['message'] . " no arquivo " . $erro['file'] . " linha " . $erro['line']);
    }
});

// Inicia processamento
gravarLog("--- NOVA TENTATIVA DE ENVIO ---");

try {
    // Esconde erros da tela para não quebrar o Javascript, mas grava no log
    ini_set('display_errors', '0'); 
    error_reporting(E_ALL);

    ob_start();

    // Verifica VENDOR
    $caminhoVendor = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($caminhoVendor)) {
        $caminhoVendor = __DIR__ . '/../vendor/autoload.php';
    }

    if (!file_exists($caminhoVendor)) {
        throw new Exception("Pasta vendor não encontrada. Caminho testado: $caminhoVendor");
    }

    require $caminhoVendor;
    gravarLog("Vendor carregado com sucesso.");

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception as MailerException;

    // --- CONFIGURAÇÕES ---
    $SMTP_HOST = 'mail.smtp2go.com';
    $SMTP_USER = 'disparosite@oliveiraalpinismo.com.br';
    $SMTP_PASS = '@altura@Novo2';
    $SMTP_PORT = 2525;
    $TO_EMAIL  = 'comercial@oliveiraalpinismo.com.br';

    function respond_js($msg, $url = '') {
        gravarLog("Enviando resposta ao usuario: $msg");
        ob_clean();
        echo "<!DOCTYPE html><html><body><script>alert('".addslashes($msg)."');";
        if ($url) echo "window.location.href='$url';";
        else echo "window.history.back();";
        echo "</script></body></html>";
        exit;
    }

    // Verifica Método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        gravarLog("Acesso direto via navegador (GET) detectado. Redirecionando.");
        header('Location: /');
        exit;
    }

    // Verifica Honeypot
    if (!empty($_POST['honeypot'])) {
        gravarLog("SPAM bloqueado (Honeypot preenchido).");
        die('Spam detectado');
    }

    // Dados
    $nome = $_POST['nome'] ?? '';
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $telefone = preg_replace('/\D+/', '', $_POST['telefone'] ?? '');
    
    gravarLog("Dados recebidos: Nome=$nome, Email=$email");

    if (!$email || !$nome) {
        throw new Exception("Campos obrigatórios vazios.");
    }

    // Envio
    $mail = new PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host       = $SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = $SMTP_USER;
    $mail->Password   = $SMTP_PASS;
    $mail->Port       = $SMTP_PORT;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->CharSet    = 'UTF-8';
    
    $mail->setFrom($SMTP_USER, 'Site Oliveira Alpinismo');
    $mail->addAddress($TO_EMAIL);
    $mail->addReplyTo($email, $nome);

    $mail->isHTML(true);
    $mail->Subject = "Orcamento Site: $nome";
    $mail->Body    = "<h3>Novo Contato</h3><p>Nome: $nome</p><p>Email: $email</p><p>Telefone: $telefone</p><p>Msg: " . nl2br(htmlspecialchars($_POST['descricao'] ?? '')) . "</p>";

    $mail->send();
    
    gravarLog("SUCESSO: E-mail enviado para o SMTP.");
    respond_js('Enviado com sucesso!', 'https://oliveiraalpinismo.com.br/sucesso');

} catch (MailerException $e) {
    gravarLog("ERRO PHPMAILER: " . $e->getMessage());
    respond_js("Erro ao enviar e-mail. Tente novamente.");
} catch (Throwable $t) {
    gravarLog("ERRO GERAL: " . $t->getMessage() . " na linha " . $t->getLine());
    respond_js("Ocorreu um erro no sistema.");
}