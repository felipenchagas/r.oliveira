<?php
// IMPORTANTE: Estas linhas devem ficar no topo absoluto do arquivo
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Carrega o autoloader (confira se o caminho da pasta vendor está certo)
require 'vendor/autoload.php';

// Verifica se a requisição é um POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Coleta os dados do formulário (com valores padrão para evitar erro undefined)
    $nome     = $_POST['nome'] ?? 'Sem nome';
    $email    = $_POST['email'] ?? 'Sem email';
    $telefone = $_POST['telefone'] ?? 'Sem telefone';
    $mensagem = $_POST['mensagem'] ?? 'Sem mensagem';

    $mail = new PHPMailer(true);

    try {
        // --- Configurações do Servidor ---
        $mail->isSMTP();
        $mail->Host       = 'mail.oliveiraalpinismo.com.br';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'contato@oliveiraalpinismo.com.br';
        $mail->Password   = '@altura@Novo2'; // Senha inserida aqui
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Porta 465 (SSL)
        $mail->Port       = 465;

        // --- Remetente e Destinatário ---
        $mail->setFrom('contato@oliveiraalpinismo.com.br', 'Site Oliveira Alpinismo');
        $mail->addAddress('contato@oliveiraalpinismo.com.br'); 
        
        // Se quiser que o cliente receba uma cópia, descomente a linha abaixo:
        // $mail->addAddress($email); 

        // --- Conteúdo do Email ---
        $mail->isHTML(true);
        $mail->Subject = "Contato Site: $nome";
        $mail->Body    = "
            <h3>Novo contato recebido pelo site</h3>
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
    // Caso tentem acessar o arquivo direto pelo navegador sem enviar dados
    echo "Aguardando envio do formulário...";
}