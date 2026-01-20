<?php
// --- CONFIGURAÇÕES DE DEBUG (Pode remover depois se quiser) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- IMPORTAÇÕES DEVEM FICAR NO TOPO ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Carrega o autoloader
require 'vendor/autoload.php';

// Verifica se a requisição é um POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Coleta dados
    $nome     = $_POST['nome'] ?? 'Sem nome';
    $email    = $_POST['email'] ?? 'Sem email';
    $telefone = $_POST['telefone'] ?? 'Sem telefone';
    $mensagem = $_POST['mensagem'] ?? 'Sem mensagem';

    $mail = new PHPMailer(true);

    try {
        // --- Configurações do Servidor (Porta 465 SSL) ---
        $mail->isSMTP();
        $mail->Host       = 'mail.oliveiraalpinismo.com.br'; // Endereço do host
        $mail->SMTPAuth   = true;
        $mail->Username   = 'contato@oliveiraalpinismo.com.br';
        $mail->Password   = '@altura@Novo2';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Criptografia SSL Implícita
        $mail->Port       = 465;

        // IMPORTANTE: Ignora verificação de certificado (Corrige erro de conexão local)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // --- Remetente e Destinatário ---
        $mail->setFrom('contato@oliveiraalpinismo.com.br', 'Site Oliveira Alpinismo');
        $mail->addAddress('contato@oliveiraalpinismo.com.br');
        // $mail->addAddress($email); // Se quiser enviar cópia para o cliente

        // --- Conteúdo ---
        $mail->isHTML(true);
        $mail->Subject = "Contato Site: $nome";
        $mail->Body    = "
            <h3>Novo contato recebido</h3>
            <p><strong>Nome:</strong> $nome</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Telefone:</strong> $telefone</p>
            <p><strong>Mensagem:</strong><br>$mensagem</p>
        ";
        $mail->AltBody = "Nome: $nome\nEmail: $email\nTelefone: $telefone\nMensagem: $mensagem";

        $mail->send();
        echo 'Mensagem enviada com sucesso';

    } catch (Exception $e) {
        // Mostra o erro exato na tela
        echo "Erro ao enviar mensagem: {$mail->ErrorInfo}";
    }
} else {
    echo "Aguardando envio via formulário...";
}