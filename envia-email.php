<?php
// DEBUG (remova depois se quiser)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Dados do formulário
    $nome     = $_POST['nome'] ?? 'Sem nome';
    $email    = $_POST['email'] ?? 'Sem email';
    $telefone = $_POST['telefone'] ?? 'Sem telefone';
    $mensagem = $_POST['mensagem'] ?? 'Sem mensagem';

    $mail = new PHPMailer(true);

    try {
        // === SMTP2GO ===
        $mail->isSMTP();
        $mail->Host       = 'mail.smtp2go.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'disparosite@oliveiraalpinismo.com.br';
        $mail->Password   = '@altura@Novo2';

        // SSL implícito (porta 465)
        $mail->Port       = 465;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

        // Debug (use apenas para teste)
        $mail->SMTPDebug  = 0; // coloque 2 se quiser ver o log
        $mail->Timeout    = 20;

        // Remetente
        $mail->setFrom(
            'disparosite@oliveiraalpinismo.com.br',
            'Site Oliveira Alpinismo'
        );

        // Reply-To = email do cliente
        if ($email !== 'Sem email') {
            $mail->addReplyTo($email, $nome);
        }

        // Destinatário
        $mail->addAddress('comercial@oliveiraalpinismo.com.br');

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = "Contato Site: $nome";

        $mail->Body = "
            <h3>Novo contato recebido</h3>
            <p><strong>Nome:</strong> " . htmlspecialchars($nome) . "</p>
            <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
            <p><strong>Telefone:</strong> " . htmlspecialchars($telefone) . "</p>
            <p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($mensagem)) . "</p>
        ";

        $mail->AltBody =
            "Nome: $nome\n" .
            "Email: $email\n" .
            "Telefone: $telefone\n" .
            "Mensagem: $mensagem";

        // Envia
        $mail->send();

        echo 'Mensagem enviada com sucesso';

    } catch (Exception $e) {
        echo "Erro ao enviar mensagem: {$mail->ErrorInfo}";
    }

} else {
    echo 'Aguardando envio via formulário...';
}
