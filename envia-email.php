<?php
// AS LINHAS "USE" TÊM QUE SER AS PRIMEIRAS, FORA DE QUALQUER IF
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Carrega o autoloader do Composer (confira se o caminho 'vendor' está correto nessa pasta)
require 'vendor/autoload.php';

// Verifica se houve POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nome = $_POST['nome'] ?? 'Sem nome';
    $email = $_POST['email'] ?? 'Sem email';
    $telefone = $_POST['telefone'] ?? 'Sem telefone';
    $mensagem = $_POST['mensagem'] ?? 'Sem mensagem';

    $mail = new PHPMailer(true);

    try {
        // --- Configurações do Servidor ---
        $mail->isSMTP();
        $mail->Host       = 'mail.oliveiraalpinismo.com.br'; // Verifique o host exato
        $mail->SMTPAuth   = true;
        $mail->Username   = 'contato@oliveiraalpinismo.com.br'; // Seu email de envio
        $mail->Password   = '@altura@Novo2'; // <--- COLOQUE A SENHA AQUI
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Ou ENCRYPTION_STARTTLS com porta 587
        $mail->Port       = 465; // Ou 587

        // --- Remetente e Destinatário ---
        $mail->setFrom('contato@oliveiraalpinismo.com.br', 'Site Oliveira Alpinismo');
        $mail->addAddress('contato@oliveiraalpinismo.com.br'); // Quem recebe
        // $mail->addAddress('outro@email.com'); // Cópia opcional

        // --- Conteúdo ---
        $mail->isHTML(true);
        $mail->Subject = "Novo contato pelo site: $nome";
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
    echo "Nenhum dado recebido via POST.";
}