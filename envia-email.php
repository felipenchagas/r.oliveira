<?php
// =====================================================
// CONFIG (debug: desligue em produção se quiser)
// =====================================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// =====================================================
// Helper: sanitização simples
// =====================================================
function safe_text(string $v): string {
    return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

// =====================================================
// Só aceita POST
// =====================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: https://oliveiraalpinismo.com.br/');
    exit;
}

// =====================================================
// Anti-spam (honeypot + tempo mínimo)
// =====================================================
$honeypot = (string)($_POST['honeypot'] ?? '');
$formLoadedAt = (string)($_POST['form_loaded_at'] ?? '');

if (trim($honeypot) !== '') {
    // Bot preencheu o campo escondido
    header('Location: https://oliveiraalpinismo.com.br/');
    exit;
}

if ($formLoadedAt !== '' && ctype_digit($formLoadedAt)) {
    $elapsed = time() - (int)$formLoadedAt;
    // menos de 3s costuma ser bot (ajuste se quiser)
    if ($elapsed >= 0 && $elapsed < 3) {
        header('Location: https://oliveiraalpinismo.com.br/');
        exit;
    }
}

// =====================================================
// Coleta campos do formulário (aceita nomes antigos e novos)
// =====================================================
$nome   = (string)($_POST['nome'] ?? '');
$email  = (string)($_POST['email'] ?? '');

$ddd      = (string)($_POST['ddd'] ?? '');
$telNum   = (string)($_POST['telefone'] ?? ''); // no seu form: "telefone" é o número (9 dígitos)
$telefoneLegacy = (string)($_POST['telefone_completo'] ?? ''); // opcional, caso exista em alguma versão antiga

$cidade = (string)($_POST['cidade'] ?? '');
$estado = (string)($_POST['estado'] ?? '');

// No seu HTML o textarea se chama "descricao". Mantemos compatibilidade com "mensagem".
$descricao = (string)($_POST['descricao'] ?? ($_POST['mensagem'] ?? ''));

// Monta telefone completo
$telefoneFull = trim($telefoneLegacy);
if ($telefoneFull === '') {
    $p1 = preg_replace('/\D+/', '', $ddd);
    $p2 = preg_replace('/\D+/', '', $telNum);
    $telefoneFull = trim(($p1 !== '' ? "($p1) " : '') . ($p2 !== '' ? $p2 : ''));
}

// Sanitiza (texto)
$nomeSafe     = safe_text($nome ?: 'Sem nome');
$emailSafe    = safe_text($email ?: 'Sem email');

$dddSafe      = safe_text(preg_replace('/\D+/', '', $ddd) ?: 'N/D');
$telNumSafe   = safe_text(preg_replace('/\D+/', '', $telNum) ?: 'N/D');
$telefoneSafe = safe_text($telefoneFull ?: 'Sem telefone');

$cidadeSafe   = safe_text($cidade ?: 'N/D');
$estadoSafe   = safe_text(strtoupper($estado) ?: 'N/D');

// Descrição: preserva quebras de linha
$descricaoRaw  = trim((string)$descricao);
$descricaoSafe = nl2br(htmlspecialchars($descricaoRaw ?: 'Sem mensagem', ENT_QUOTES, 'UTF-8'));

// Extras úteis
$dataHora  = date('d/m/Y H:i:s');
$ip        = $_SERVER['REMOTE_ADDR'] ?? 'N/D';
$userAgent = safe_text($_SERVER['HTTP_USER_AGENT'] ?? 'N/D');

// URLs
$siteUrl    = 'https://oliveiraalpinismo.com.br';
$sucessoUrl = $siteUrl . '/sucesso';
$logoUrl    = $siteUrl . '/assets/imgs/logo/logo.png';

// =====================================================
// Monta e-mail HTML
// =====================================================
$subject = "Novo contato - Oliveira Alpinismo | {$nomeSafe}";

$html = '
<div style="margin:0;padding:0;background:#f3f6fb;">
  <div style="max-width:760px;margin:0 auto;padding:26px 14px;">

    <div style="background:#ffffff;border:1px solid rgba(16,24,40,.12);border-radius:18px;overflow:hidden;box-shadow:0 18px 46px rgba(16,24,40,.12);">

      <div style="height:6px;background:linear-gradient(90deg,transparent,#d6b25e,#f3da8a,#d6b25e,transparent);"></div>

      <div style="padding:22px 22px 10px 22px;">
        <img src="'.$logoUrl.'" alt="Oliveira Alpinismo" style="height:44px;width:auto;display:block;margin:0 0 14px 0;">

        <span style="display:inline-block;padding:8px 12px;border-radius:999px;border:1px solid rgba(22,163,74,.18);background:#eaf7ef;color:#16a34a;font-weight:800;font-family:Arial,sans-serif;font-size:13px;">
          ✅ Novo contato recebido pelo site
        </span>

        <h2 style="margin:14px 0 6px 0;color:#101828;font-family:Arial,sans-serif;font-size:22px;letter-spacing:-.2px;">
          Solicitação de contato / orçamento
        </h2>

        <p style="margin:0 0 14px 0;color:#475467;font-family:Arial,sans-serif;font-size:14px;line-height:1.7;">
          Dados enviados pelo visitante:
        </p>
      </div>

      <div style="padding:0 22px 18px 22px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:separate;border-spacing:0;">
          <tr>
            <td style="padding:14px;border:1px solid rgba(16,24,40,.10);border-radius:14px;background:#fbfcff;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;">

                <tr>
                  <td style="padding:10px 0;border-bottom:1px solid rgba(16,24,40,.08);">
                    <div style="color:#667085;font-family:Arial,sans-serif;font-size:12px;">NOME</div>
                    <div style="color:#101828;font-family:Arial,sans-serif;font-size:16px;font-weight:800;">'.$nomeSafe.'</div>
                  </td>
                </tr>

                <tr>
                  <td style="padding:10px 0;border-bottom:1px solid rgba(16,24,40,.08);">
                    <div style="color:#667085;font-family:Arial,sans-serif;font-size:12px;">E-MAIL</div>
                    <div style="color:#101828;font-family:Arial,sans-serif;font-size:15px;font-weight:800;">'.$emailSafe.'</div>
                  </td>
                </tr>

                <tr>
                  <td style="padding:10px 0;border-bottom:1px solid rgba(16,24,40,.08);">
                    <div style="color:#667085;font-family:Arial,sans-serif;font-size:12px;">DDD</div>
                    <div style="color:#101828;font-family:Arial,sans-serif;font-size:15px;font-weight:800;">'.$dddSafe.'</div>
                  </td>
                </tr>

                <tr>
                  <td style="padding:10px 0;border-bottom:1px solid rgba(16,24,40,.08);">
                    <div style="color:#667085;font-family:Arial,sans-serif;font-size:12px;">TELEFONE</div>
                    <div style="color:#101828;font-family:Arial,sans-serif;font-size:15px;font-weight:800;">'.$telefoneSafe.'</div>
                  </td>
                </tr>

                <tr>
                  <td style="padding:10px 0;border-bottom:1px solid rgba(16,24,40,.08);">
                    <div style="color:#667085;font-family:Arial,sans-serif;font-size:12px;">CIDADE</div>
                    <div style="color:#101828;font-family:Arial,sans-serif;font-size:15px;font-weight:800;">'.$cidadeSafe.'</div>
                  </td>
                </tr>

                <tr>
                  <td style="padding:10px 0;border-bottom:1px solid rgba(16,24,40,.08);">
                    <div style="color:#667085;font-family:Arial,sans-serif;font-size:12px;">ESTADO</div>
                    <div style="color:#101828;font-family:Arial,sans-serif;font-size:15px;font-weight:800;">'.$estadoSafe.'</div>
                  </td>
                </tr>

                <tr>
                  <td style="padding:10px 0;">
                    <div style="color:#667085;font-family:Arial,sans-serif;font-size:12px;">DESCRIÇÃO DO ORÇAMENTO</div>
                    <div style="color:#101828;font-family:Arial,sans-serif;font-size:14px;line-height:1.75;">'.$descricaoSafe.'</div>
                  </td>
                </tr>

              </table>
            </td>
          </tr>
        </table>

        <div style="margin-top:14px;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
            <tr>
              <td style="padding-right:10px;padding-bottom:10px;">
                <a href="mailto:'.$emailSafe.'" style="display:inline-block;padding:12px 14px;border-radius:12px;text-decoration:none;font-family:Arial,sans-serif;font-weight:900;font-size:13px;color:#1b1304;background:linear-gradient(180deg,#f3da8a,#d6b25e);border:1px solid rgba(214,178,94,.40);">
                  Responder por e-mail
                </a>
              </td>
              <td style="padding-bottom:10px;">
                <a href="'.$siteUrl.'" style="display:inline-block;padding:12px 14px;border-radius:12px;text-decoration:none;font-family:Arial,sans-serif;font-weight:900;font-size:13px;color:#101828;background:#ffffff;border:1px solid rgba(16,24,40,.14);">
                  Abrir site
                </a>
              </td>
            </tr>
          </table>
        </div>

        <div style="margin-top:12px;padding-top:14px;border-top:1px solid rgba(16,24,40,.10);color:#667085;font-family:Arial,sans-serif;font-size:12px;line-height:1.6;">
          <strong>Data/Hora:</strong> '.$dataHora.' &nbsp;|&nbsp; <strong>IP:</strong> '.$ip.'<br>
          <span style="color:#98a2b3;">User-Agent:</span> '.$userAgent.'
        </div>

      </div>
    </div>

  </div>
</div>
';

// Texto puro (fallback)
$alt =
"Novo contato recebido pelo site\n\n".
"Nome: {$nomeSafe}\n".
"Email: {$emailSafe}\n".
"DDD: {$dddSafe}\n".
"Telefone: {$telefoneSafe}\n".
"Cidade: {$cidadeSafe}\n".
"Estado: {$estadoSafe}\n\n".
"Descrição do orçamento:\n".($descricaoRaw ?: 'Sem mensagem')."\n\n".
"Data/Hora: {$dataHora}\n".
"IP: {$ip}\n";

// =====================================================
// Envio com PHPMailer (SMTP2GO)
// =====================================================
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'mail.smtp2go.com';
    $mail->SMTPAuth   = true;

    // IMPORTANTE: mantenha suas credenciais aqui
    $mail->Username   = 'disparosite@oliveiraalpinismo.com.br';
    $mail->Password   = '@altura@Novo2';

    // 465 = SSL implícito
    $mail->Port       = 465;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

    // evita ficar carregando “pra sempre”
    $mail->Timeout = 20;
    $mail->SMTPDebug = 0; // se precisar ver log: 2

    // De/Para
    $mail->setFrom('disparosite@oliveiraalpinismo.com.br', 'Site Oliveira Alpinismo');
    if ($emailSafe !== 'Sem email') {
        $mail->addReplyTo($emailSafe, $nomeSafe);
    }
    $mail->addAddress('comercial@oliveiraalpinismo.com.br');

    // Conteúdo
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $html;
    $mail->AltBody = $alt;

    $mail->send();

    // Redireciona para página de sucesso
    header("Location: {$sucessoUrl}");
    exit;

} catch (Exception $e) {
    echo "Erro ao enviar mensagem: {$mail->ErrorInfo}";
    exit;
}
