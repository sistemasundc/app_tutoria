<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailerDerivacion
{
  private $cfg;

  public function __construct()
  {
    $this->cfg = require __DIR__ . '/../config/mail_config.php';
  }

  private function baseMailer(): PHPMailer
  {
    $m = new PHPMailer(true);
    $m->CharSet   = 'UTF-8';
    $m->isSMTP();
    $m->Host       = $this->cfg['host'];
    $m->SMTPAuth   = true;
    $m->Username   = $this->cfg['username'];
    $m->Password   = $this->cfg['password'];
    $m->SMTPSecure = $this->cfg['encryption']; // tls | ssl
    $m->Port       = $this->cfg['port'];
    $m->setFrom($this->cfg['from_email'], $this->cfg['from_name']);
    if (!empty($this->cfg['bcc']) && is_array($this->cfg['bcc'])) {
      foreach ($this->cfg['bcc'] as $bcc) $m->addBCC($bcc);
    }
    return $m;
  }

 public function enviar($to, array $data): bool
  {
    $mail = $this->baseMailer();

    // Destinatarios (string o array)
    if (is_array($to)) { foreach ($to as $t) if ($t) $mail->addAddress(trim($t)); }
    else { $mail->addAddress($to); }

    // Responder al tutor
    if (!empty($data['tutor']['correo'])) {
      $mail->addReplyTo($data['tutor']['correo'], $data['tutor']['nombre'] ?? '');
      // $mail->addCC($data['tutor']['correo']); // opcional
    }

    // === Logo (CID embebido) ===
    $cidLogo = null;
    $logoPath = __DIR__ . '/../img/logo-uni.png';  // <-- ajusta si usas otra ruta/nombre
    if (file_exists($logoPath)) {
      // 'logo_undc' es el CID que se usará en el HTML
      $mail->addEmbeddedImage($logoPath, 'logo_undc', 'logo-uni.png');
      $cidLogo = 'cid:logo_undc';
    } else {
      // Fallback: si no existe el archivo local, usa una URL pública si tienes una
      $cidLogo = 'https://via.placeholder.com/96x96?text=UNDC'; // opcional
    }

    $mail->isHTML(true);
    $mail->Subject = "NUEVO ESTUDIANTE DERIVADO A {$data['area']}";

    // Colores / estilos inline (compatibles con clientes de correo)
    $bg   = '#f5f7fb';
    $card = '#ffffff';
    $head = '#1e73be';   // azul cabecera
    $bar  = '#2f7fc9';   // azul secciones
    $txt  = '#222222';
    $mut  = '#6b7280';
    $btn  = '#2563eb';   // azul botón

    $html = '
    <div style="margin:0;padding:0;background:'.$bg.';width:100%;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="width:100%;max-width:760px;margin:0 auto;padding:24px;">
        <tr>
          <td>
            <!-- Card -->
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;background:'.$card.';border-radius:12px;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,.08);">
              <!-- Header con logo -->
              <tr>
                <td style="padding:28px 24px 18px 24px;text-align:center;background:'.$card.';">
                  <img src="'.$cidLogo.'" alt="UNDC" width="96" height="96" style="display:block;margin:0 auto 8px;border-radius:50%;"/>
                  <div style="font:bold 18px Arial,Helvetica,sans-serif;color:'.$mut.';letter-spacing:.4px;">
                    UNIVERSIDAD NACIONAL DE CAÑETE
                  </div>
                </td>
              </tr>

              <!-- Título -->
              <tr>
                <td style="padding:0 24px 4px 24px;text-align:center;">
                  <div style="font:700 20px Arial,Helvetica,sans-serif;color:'.$txt.';">
                    NUEVO ESTUDIANTE DERIVADO A '.htmlspecialchars($data['area']).'
                  </div>
                </td>
              </tr>

              <!-- Separador -->
              <tr><td style="height:12px;"></td></tr>

              <!-- Contenido -->
              <tr>
                <td style="padding:0 24px 24px 24px;">
                  <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                    <tr>
                      <td colspan="2" style="background:'.$bar.';color:#fff;padding:10px 12px;font:700 14px Arial,Helvetica,sans-serif;">
                        DATOS DEL ESTUDIANTE
                      </td>
                    </tr>
                    <tr>
                      <td style="border-bottom:1px solid #e5e7eb;padding:10px 12px;font:700 13px Arial,Helvetica,sans-serif;color:'.$txt.';">Estudiante:</td>
                      <td style="border-bottom:1px solid #e5e7eb;padding:10px 12px;font:13px Arial,Helvetica,sans-serif;color:'.$txt.';">'.htmlspecialchars($data['estu']['nombre'] ?? '').'</td>
                    </tr>
                    <tr>
                      <td style="border-bottom:1px solid #e5e7eb;padding:10px 12px;font:700 13px Arial,Helvetica,sans-serif;color:'.$txt.';">Correo:</td>
                      <td style="border-bottom:1px solid #e5e7eb;padding:10px 12px;font:13px Arial,Helvetica,sans-serif;">
                        <a href="mailto:'.htmlspecialchars($data['estu']['correo'] ?? '').'" style="color:'.$head.';text-decoration:none;">'.htmlspecialchars($data['estu']['correo'] ?? '').'</a>
                      </td>
                    </tr>
                    <tr>
                      <td style="border-bottom:1px solid #e5e7eb;padding:10px 12px;
                                font:700 13px Arial,Helvetica,sans-serif;color:'.$txt.';">
                        Carrera:
                      </td>
                      <td style="border-bottom:1px solid #e5e7eb;padding:0;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                              style="width:100%;border-collapse:collapse;">
                          <tr>
                            <!-- Carrera -->
                            <td style="padding:10px 12px;border-right:1px solid #e5e7eb;
                                      font:13px Arial,Helvetica,sans-serif;color:'.$txt.';">
                              '.htmlspecialchars($data['estu']['carrera'] ?: '—').'
                            </td>
                            <!-- Ciclo -->
                            <td style="padding:10px 12px;border-right:1px solid #e5e7eb;
                                      font:13px Arial,Helvetica,sans-serif;color:'.$txt.';">
                              Ciclo: '.htmlspecialchars($data['estu']['ciclo'] ?: '—').'
                            </td>
                            <!-- Turno - Sección -->
                            <td style="padding:10px 12px;
                                      font:13px Arial,Helvetica,sans-serif;color:'.$txt.';">
                              '.htmlspecialchars($data['estu']['turno'] ?: '—')
                              .' - '.htmlspecialchars($data['estu']['seccion'] ?: '—').'
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="2" style="background:'.$bar.';color:#fff;padding:10px 12px;font:700 14px Arial,Helvetica,sans-serif;">
                        MOTIVO DE DERIVACIÓN
                      </td>
                    </tr>
                    <tr>
                      <td colspan="2" style="padding:12px;font:13px Arial,Helvetica,sans-serif;color:'.$txt.';">'.nl2br(htmlspecialchars($data['motivo'] ?? '')).'</td>
                    </tr>
                    <tr>
                      <td colspan="2" style="background:'.$bar.';color:#fff;padding:10px 12px;font:700 14px Arial,Helvetica,sans-serif;">
                        DERIVADO POR:
                      </td>
                    </tr>
                    <tr>
                      <td style="border-bottom:1px solid #e5e7eb;padding:10px 12px;font:700 13px Arial,Helvetica,sans-serif;color:'.$txt.';">Tutor(a):</td>
                      <td style="border-bottom:1px solid #e5e7eb;padding:10px 12px;font:13px Arial,Helvetica,sans-serif;color:'.$txt.';">'.htmlspecialchars($data['tutor']['nombre'] ?? '').'</td>
                    </tr>
                    <tr>
                      <td style="border-bottom:1px solid #e5e7eb;padding:10px 12px;font:700 13px Arial,Helvetica,sans-serif;color:'.$txt.';">Correo:</td>
                      <td style="border-bottom:1px solid #e5e7eb;padding:10px 12px;font:13px Arial,Helvetica,sans-serif;">
                        <a href="mailto:'.htmlspecialchars($data['tutor']['correo'] ?? '').'" style="color:'.$head.';text-decoration:none;">'.htmlspecialchars($data['tutor']['correo'] ?? '').'</a>
                      </td>
                    </tr>

                  </table>

                  <!-- Fecha -->
                  <div style="margin:14px 2px 4px 2px;font:12px Arial,Helvetica,sans-serif;color:'.$mut.';">
                    Fecha de derivación: '.htmlspecialchars($data['fecha']).'
                  </div>

                  <!-- Botón -->
                  <div style="text-align:center;margin:16px 0 8px;">
                    <a href="'.htmlspecialchars($data['link']).'" target="_blank" rel="noopener"
                      style="background:'.$btn.';color:#fff;text-decoration:none;padding:12px 22px;border-radius:8px;display:inline-block;font:600 14px Arial,Helvetica,sans-serif;">
                      IR A SISTECU - UNDC
                    </a>
                  </div>
                </td>
              </tr>
              <!-- Footer -->
              <tr>
                <td style="padding:12px 24px 20px 24px;text-align:center;color:'.$mut.';font:12px Arial,Helvetica,sans-serif;">
                  MENSAJE AUTOMÁTICO DEL SISTECU - UNDC.
                </td>
              </tr>
            </table>
            <!-- /Card -->
          </td>
        </tr>
      </table>
    </div>';

    $mail->Body = $html;
    $mail->AltBody = "NUEVO ESTUDIANTE DERIVADO A {$data['area']}\n".
                    "Estudiante: ".($data['estu']['nombre'] ?? '')."\n".
                    "Tutor: ".($data['tutor']['nombre'] ?? '')."\n".
                    "Motivo: ".($data['motivo'] ?? '')."\n".
                    "IR A SISTECU - UNDC: ".($data['link'] ?? '');

    try { return $mail->send(); }
    catch (Exception $e) { error_log('[MAIL ERROR] '.$e->getMessage()); return false; }
  }

}
