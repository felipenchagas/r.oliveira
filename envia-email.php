<?php
// AS LINHAS "USE" DEVEM FICAR SEMPRE NO TOPO
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Carrega o autoloader
require 'vendor/autoload.php';

// Verifica se é POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nome     = $_POST['nome'] ?? 'Sem nome';
    $email    = $_POST['email'] ?? 'Sem email';
    $telefone = $_POST['telefone'] ?? 'Sem telefone';
    $mensagem = $_POST['mensagem'] ?? 'Sem mensagem';

    $mail = new PHPMailer(true);

    try {
        // --- Configurações de Servidor (Modo Localhost Seguro) ---
        $mail->isSMTP();
        $mail->Host       = 'localhost'; // Conecta internamente
        $mail->SMTPAuth   = true;
        $mail->Username   = 'contato@oliveiraalpinismo.com.br';
        $mail->Password   = '@altura@Novo2';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS Explicito
        $mail->Port       = 587;

        // Configuração extra para aceitar SSL local sem erro
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
        echo "Erro ao enviar mensagem: {$mail->ErrorInfo}";
    }
} else {
    echo "Aguardando envio do formulário...";
}