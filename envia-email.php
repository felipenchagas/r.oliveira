<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome     = $_POST['nome'] ?? 'Sem nome';
    $email    = $_POST['email'] ?? 'Sem email';
    $telefone = $_POST['telefone'] ?? 'Sem telefone';
    $mensagem = $_POST['mensagem'] ?? 'Sem mensagem';

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'mail.smtp2go.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'disparosite@oliveiraalpinismo.com.br';
        $mail->Password   = '@altura@Novo2';

        $mail->Port       = 465;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

        $mail->SMTPDebug  = 0;
        $mail->Timeout    = 20;

        $mail->setFrom('disparosite@oliveiraalpinismo.com.br', 'Site Oliveira Alpinismo');

        if ($email !== 'Sem email') {
            $mail->addReplyTo($email, $nome);
        }

        $mail->addAddress('comercial@oliveiraalpinismo.com.br');

        $mail->isHTML(true);
        $mail->Subject = "Contato Site: $nome";
        $mail->Body    = "
            <h3>Novo contato recebido</h3>
            <p><strong>Nome:</strong> " . htmlspecialchars($nome) . "</p>
            <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
            <p><strong>Telefone:</strong> " . htmlspecialchars($telefone) . "</p>
            <p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($mensagem)) . "</p>
        ";
        $mail->AltBody = "Nome: $nome\nEmail: $email\nTelefone: $telefone\nMensagem: $mensagem";

        $mail->send();

        header('Location: https://oliveiraalpinismo.com.br/sucesso');
        exit;

    } catch (Exception $e) {
        // se der erro, pode mandar pra uma página de erro também
        echo "Erro ao enviar mensagem: {$mail->ErrorInfo}";
        exit;
    }

} else {
    header('Location: https://oliveiraalpinismo.com.br/');
    exit;
}
