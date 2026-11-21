<?php
/**
 * Poseidon Yachting Caraïbes – envoi des demandes de watertoys
 * Hébergement OVH (PHP + mail()). Envoie :
 *   1. un e-mail à contact@poseidonyachtingcaraibes.com (en français)
 *   2. une copie au client (dans la langue choisie sur le site)
 */
header('Content-Type: application/json; charset=utf-8');

const PYC_EMAIL = 'contact@poseidonyachtingcaraibes.com';
const PYC_NAME  = 'Poseidon Yachting Caraïbes';
const PRICES    = ['half' => 150, 'full' => 250, 'efoil' => 500];

function out($ok, $code = '') { echo json_encode(['ok' => $ok, 'code' => $code]); exit; }
function clean($v, $max = 200) {
    $v = trim((string)$v);
    $v = str_replace(["\r", "\n", "%0a", "%0d"], ' ', $v);   // empêche l'injection d'en-têtes
    return mb_substr(strip_tags($v), 0, $max);
}
function h($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'method');
if (!empty($_POST['website'])) out(true, 'hp');            // pot de miel anti-robots : on fait semblant

$lang    = in_array($_POST['lang'] ?? '', ['fr', 'en', 'es', 'pt'], true) ? $_POST['lang'] : 'fr';
$boat    = clean($_POST['boat'] ?? '', 120);
$date    = clean($_POST['date'] ?? '', 10);
$type    = ($_POST['type'] ?? '') === 'full' ? 'full' : 'half';
$vapor   = max(0, min(6, (int)($_POST['vapor'] ?? 0)));
$efoil   = max(0, min(4, (int)($_POST['efoil'] ?? 0)));
$name    = clean($_POST['name'] ?? '', 120);
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone   = clean($_POST['phone'] ?? '', 40);
$message = clean($_POST['message'] ?? '', 600);
$consent = !empty($_POST['consent']);

if (!$boat || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !$name || !$email || !$consent) out(false, 'invalid');
if ($vapor === 0 && $efoil === 0 && $message === '') out(false, 'empty');

$total   = $vapor * PRICES[$type] + $efoil * PRICES['efoil'];
$dateFr  = implode('/', array_reverse(explode('-', $date)));

$L = [
  'fr' => ['subj' => 'Votre demande de watertoys – Poseidon Yachting Caraïbes', 'hello' => 'Bonjour', 'intro' => 'Merci pour votre demande. Voici le récapitulatif. Nous confirmons la disponibilité du matériel sous 24 h.', 'boat' => 'Bateau', 'date' => 'Date', 'dur' => 'Durée', 'half' => '½ journée', 'full' => 'Journée', 'vapor' => 'Vapor Pro supplémentaires', 'efoil' => 'Sessions eFoil (2 h)', 'incl' => '1 Vapor Pro inclus à bord', 'total' => 'Total watertoys estimé', 'phone' => 'Téléphone', 'msg' => 'Commentaire', 'end' => 'À bientôt à bord !'],
  'en' => ['subj' => 'Your water toy request – Poseidon Yachting Caraïbes', 'hello' => 'Hello', 'intro' => 'Thank you for your request. Here is your summary. We confirm equipment availability within 24 hours.', 'boat' => 'Boat', 'date' => 'Date', 'dur' => 'Duration', 'half' => 'Half day', 'full' => 'Full day', 'vapor' => 'Extra Vapor Pros', 'efoil' => 'eFoil sessions (2 hrs)', 'incl' => '1 Vapor Pro included on board', 'total' => 'Estimated water toy total', 'phone' => 'Phone', 'msg' => 'Comment', 'end' => 'See you on board!'],
  'es' => ['subj' => 'Tu solicitud de juguetes acuáticos – Poseidon Yachting Caraïbes', 'hello' => 'Hola', 'intro' => 'Gracias por tu solicitud. Aquí tienes el resumen. Confirmamos la disponibilidad del equipo en 24 h.', 'boat' => 'Barco', 'date' => 'Fecha', 'dur' => 'Duración', 'half' => 'Medio día', 'full' => 'Día completo', 'vapor' => 'Vapor Pro adicionales', 'efoil' => 'Sesiones de eFoil (2 h)', 'incl' => '1 Vapor Pro incluido a bordo', 'total' => 'Total estimado de juguetes', 'phone' => 'Teléfono', 'msg' => 'Comentario', 'end' => '¡Nos vemos a bordo!'],
  'pt' => ['subj' => 'Sua solicitação de brinquedos aquáticos – Poseidon Yachting Caraïbes', 'hello' => 'Olá', 'intro' => 'Obrigado pela sua solicitação. Aqui está o resumo. Confirmamos a disponibilidade do equipamento em até 24 h.', 'boat' => 'Barco', 'date' => 'Data', 'dur' => 'Duração', 'half' => 'Meio período', 'full' => 'Dia inteiro', 'vapor' => 'Vapor Pro adicionais', 'efoil' => 'Sessões de eFoil (2 h)', 'incl' => '1 Vapor Pro incluído a bordo', 'total' => 'Total estimado dos brinquedos', 'phone' => 'Telefone', 'msg' => 'Comentário', 'end' => 'Até breve a bordo!'],
];

function table($t, $d) {
    $rows = '';
    foreach ($d as $k => $v) {
        if ($v === '' || $v === null) continue;
        $rows .= '<tr><td style="padding:8px 12px;color:#8E9196;border-bottom:1px solid #DCD8CE">' . h($k) . '</td><td style="padding:8px 12px;font-weight:600;color:#1C2C5B;border-bottom:1px solid #DCD8CE">' . h($v) . '</td></tr>';
    }
    return '<table style="border-collapse:collapse;width:100%;max-width:560px;font-family:Arial,sans-serif;font-size:14px">' . $rows . '</table>';
}
function wrap($inner) {
    return '<!DOCTYPE html><html><body style="margin:0;background:#F4F1EA;padding:24px;font-family:Arial,sans-serif;color:#1A1F2B">'
         . '<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden">'
         . '<div style="background:#1C2C5B;color:#fff;padding:18px 24px;font-size:18px;letter-spacing:2px;font-weight:bold">POSEIDON YACHTING <span style="font-weight:normal;color:#C6CCDB">CARAÏBES</span></div>'
         . '<div style="padding:24px">' . $inner . '</div>'
         . '<div style="padding:16px 24px;background:#F4F1EA;font-size:12px;color:#8E9196">Poseidon Yachting Caraïbes · Rue Jeanne d\'Arc, Port de Gustavia, 97133 Saint-Barthélemy · ' . PYC_EMAIL . '</div>'
         . '</div></body></html>';
}
function send($to, $subject, $html, $replyTo) {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= 'From: ' . mb_encode_mimeheader(PYC_NAME, 'UTF-8') . ' <' . PYC_EMAIL . ">\r\n";
    $headers .= "Reply-To: $replyTo\r\n";
    return mail($to, mb_encode_mimeheader($subject, 'UTF-8'), $html, $headers, '-f' . PYC_EMAIL);
}

/* 1. E-mail interne à PYC (français) */
$F = $L['fr'];
$internal = wrap(
    '<h2 style="color:#B8292F;margin:0 0 16px">Nouvelle demande de watertoys</h2>'
  . table($F, [
        'Client' => $name, 'E-mail' => $email, $F['phone'] => $phone,
        $F['boat'] => $boat, $F['date'] => $dateFr, $F['dur'] => $F[$type],
        $F['vapor'] => (string)$vapor, $F['efoil'] => (string)$efoil,
        $F['total'] => number_format($total, 0, ',', ' ') . ' €',
        $F['msg'] => $message, 'Langue du client' => strtoupper($lang),
    ])
  . '<p style="font-size:13px;color:#8E9196;margin-top:16px">Répondre à cet e-mail écrit directement au client.</p>'
);
$okInternal = send(PYC_EMAIL, "Demande watertoys – $boat – $dateFr", $internal, $email);

/* 2. Copie au client (dans sa langue) */
$C = $L[$lang];
$client = wrap(
    '<p>' . h($C['hello']) . ' ' . h($name) . ',</p><p>' . h($C['intro']) . '</p>'
  . table($C, [
        $C['boat'] => $boat, $C['date'] => $dateFr, $C['dur'] => $C[$type],
        $C['incl'] => '✓', $C['vapor'] => (string)$vapor, $C['efoil'] => (string)$efoil,
        $C['total'] => number_format($total, 0, ',', ' ') . ' €',
        $C['phone'] => $phone, $C['msg'] => $message,
    ])
  . '<p style="margin-top:20px">' . h($C['end']) . '<br><b>Poseidon Yachting Caraïbes</b></p>'
);
$okClient = send($email, $C['subj'], $client, PYC_EMAIL);

out($okInternal, $okClient ? 'sent' : 'client_failed');
