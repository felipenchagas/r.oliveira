<?php
// Carregar classes do PHPMailer
// Se estiver usando Composer, use: require 'vendor/autoload.php';
// Se baixou manualmente, use as linhas abaixo:
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

// 1. Verificação de Segurança (Honeypot)
// Se o campo oculto 'honeypot' estiver preenchido, é um robô.
if (!empty($_POST['honeypot'])) {
    die("Erro: Atividade suspeita detectada (SPAM).");
}

// 2. Verifica se os dados foram enviados
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 3. Coleta e sanitização dos dados
    $nome      = strip_tags(trim($_POST['nome']));
    $email     = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $ddd       = strip_tags(trim($_POST['ddd']));
    $telefone  = strip_tags(trim($_POST['telefone']));
    $cidade    = strip_tags(trim($_POST['cidade']));
    $estado    = strip_tags(trim($_POST['estado']));
    $descricao = nl2br(strip_tags(trim($_POST['descricao']))); // nl2br mantem as quebras de linha

    // Validação básica
    if (empty($nome) || empty($email) || empty($telefone)) {
        echo "<script>alert('Por favor, preencha todos os campos obrigatórios.'); window.history.back();</script>";
        exit;
    }

    // Instância do PHPMailer
    $mail = new PHPMailer(true);

    try {
        // --- Configurações do Servidor (SMTP2GO) ---
        $mail->isSMTP();
        $mail->Host       = 'mail.smtp2go.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'disparosite@oliveiraalpinismo.com.br';
        $mail->Password   = '@altura@Novo2';
        
        // Configuração de Porta e Criptografia
        // Porta 2525 geralmente usa TLS
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 2525; 
        
        $mail->CharSet    = 'UTF-8'; // Aceitar acentos

        // --- Remetente e Destinatário ---
        // O "From" deve ser o e-mail autenticado (disparosite) para evitar bloqueios
        $mail->setFrom('disparosite@oliveiraalpinismo.com.br', 'Site Oliveira Alpinismo');
        
        // Destinatário (Quem recebe o pedido de orçamento)
        // OBS: Troque este e-mail pelo e-mail real da empresa que receberá os orçamentos
        $mail->addAddress('disparosite@oliveiraalpinismo.com.br'); 
        
        // Responder Para (O e-mail do cliente)
        $mail->addReplyTo($email, $nome);

        // --- Conteúdo do E-mail ---
        $mail->isHTML(true);
        $mail->Subject = "Novo Orçamento: $nome - $cidade/$estado";
        
        // Corpo do E-mail (Design simples e limpo)
        $bodyContent = "
        <div style='font-family: Arial, sans-serif; color: #333;'>
            <h2>Nova Solicitação de Orçamento</h2>
            <p><strong>Cliente:</strong> $nome</p>
            <p><strong>E-mail:</strong> $email</p>
            <p><strong>Telefone:</strong> ($ddd) $telefone</p>
            <p><strong>Local:</strong> $cidade / $estado</p>
            <hr>
            <h3>Descrição do Serviço:</h3>
            <p style='background-color: #f4f4f4; padding: 15px; border-radius: 5px;'>$descricao</p>
            <br>
            <p><small>Enviado através do formulário do site.</small></p>
        </div>
        ";
        
        $mail->Body    = $bodyContent;
        $mail->AltBody = "Novo Orçamento\n\nNome: $nome\nEmail: $email\nTel: ($ddd) $telefone\nCidade: $cidade/$estado\n\nDescrição:\n$descricao";

        $mail->send();
        
        // Sucesso: Redireciona ou exibe mensagem
        echo "<script>
            alert('Orçamento solicitado com sucesso! Entraremos em contato em breve.');
            window.location.href = 'https://oliveiraalpinismo.com.br/sucesso'; // Coloque aqui a página para onde o usuário deve voltar
        </script>";

    } catch (Exception $e) {
        echo "<script>
            alert('A mensagem não pôde ser enviada. Erro: {$mail->ErrorInfo}');
            window.history.back();
        </script>";
    }
} else {
    // Se tentar acessar o arquivo diretamente sem POST
    header("Location: index.html");
    exit;
}
?>