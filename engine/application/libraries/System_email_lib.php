<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * System_email_lib — platform-sent customer-facing emails that should land
 * under the FUNNEL OWNER's email address (not our platform brand).
 *
 * Today this covers a single email: the GDPR double opt-in confirmation that
 * fires for EU leads when they submit a funnel form. Before this library,
 * that email went through global Mailgun ("Prady From Engageleads <...>") and
 * was hardcoded in English regardless of the funnel's language. Both wrong
 * for an end-lead-facing message belonging to the funnel owner.
 *
 * Design notes:
 *   - One library owns BOTH the SMTP plumbing and the localised content. No
 *     separate template files: each language is one branch inside the send_*
 *     method. Adding a language is a single switch case in one file.
 *   - There is NO platform-Mailgun fallback. If the funnel owner hasn't
 *     configured SMTP in Settings, we log and return false. Sending under
 *     the platform brand for a funnel-owner-to-lead email would be wrong
 *     and would silently mask the owner's misconfiguration.
 *   - The library never throws to its caller. The form-submit path that
 *     uses this must never block a lead submission on an email failure.
 *
 * If/when a second platform-sent email is needed (password reset for a
 * client-portal user, etc.), add a new send_* method here. The SMTP
 * plumbing already exists in this class and can be reused.
 */
class System_email_lib
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Send the GDPR double opt-in confirmation email.
     *
     * @param int    $owner_user_id  funnels.user_id of the funnel that captured the lead
     * @param string $lang_code      funnels.language_code (e.g. 'es', 'en-gb', 'pt')
     * @param string $to_email       lead's email address
     * @param string $lead_name      lead's name (used in greeting)
     * @param string $business_name  funnel's brand / campaign name
     * @param string $confirm_url    full URL the lead clicks to confirm
     * @return bool                  true if SMTP send succeeded, false otherwise
     */
    public function send_subscription_confirm($owner_user_id, $lang_code, $to_email, $lead_name, $business_name, $confirm_url)
    {
        if (empty($to_email)) {
            log_message('warning', 'System_email_lib: subscription_confirm skipped — no recipient email');
            return false;
        }

        $creds = $this->_get_smtp_creds((int) $owner_user_id);
        if (!$creds) {
            // No SMTP configured for this funnel owner. We deliberately do NOT
            // fall back to platform Mailgun — sending under our brand for a
            // funnel-owner-to-lead email would be wrong, and the same lead's
            // drip emails would fail anyway, so this gives the owner a
            // consistent "configure SMTP" signal.
            log_message('warning', 'System_email_lib: subscription_confirm — no SMTP creds for user #' . $owner_user_id . ' — skipping send to ' . $to_email);
            return false;
        }

        $copy = $this->_subscription_confirm_copy($lang_code, $lead_name, $business_name, $confirm_url);

        try {
            $this->_send_via_smtp($creds, $to_email, $lead_name, $copy['subject'], $copy['body']);
            log_message('info', 'System_email_lib: subscription_confirm sent to ' . $to_email . ' via SMTP for user #' . $owner_user_id . ' (lang=' . $copy['lang'] . ')');
            return true;
        } catch (\Throwable $e) {
            log_message('error', 'System_email_lib: subscription_confirm SMTP send failed for ' . $to_email . ' user #' . $owner_user_id . ': ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // Content
    // ─────────────────────────────────────────────────────────────────

    /**
     * Localised copy for the subscription confirm email.
     * Returns ['subject', 'body', 'lang'] (lang is the normalised code that
     * actually picked the branch — useful for log lines).
     *
     * Layout is identical across languages; only the strings change so the
     * email looks consistent regardless of locale.
     */
    private function _subscription_confirm_copy($lang_code, $lead_name, $business_name, $confirm_url)
    {
        $lang = $this->_normalize_lang($lang_code);
        // safe-escape the dynamic bits — they're injected into HTML
        $name_safe = htmlspecialchars((string) $lead_name, ENT_QUOTES, 'UTF-8');
        $biz_safe  = htmlspecialchars((string) $business_name, ENT_QUOTES, 'UTF-8');
        $url_safe  = htmlspecialchars((string) $confirm_url, ENT_QUOTES, 'UTF-8');

        switch ($lang) {
            case 'es':
                $subject = 'Confirma tu suscripción — ' . $biz_safe;
                $heading = 'Confirma tu suscripción';
                $greeting = 'Hola ' . $name_safe . ',';
                $body_p  = 'Gracias por registrarte con ' . $biz_safe . '. Para cumplir con la normativa de protección de datos, confirma tu dirección de correo electrónico haciendo clic en el botón siguiente:';
                $cta     = 'Confirmar mi suscripción';
                $footer  = 'Si no solicitaste esto, puedes ignorar este correo de forma segura.';
                break;

            case 'it':
                $subject = 'Conferma la tua iscrizione — ' . $biz_safe;
                $heading = 'Conferma la tua iscrizione';
                $greeting = 'Ciao ' . $name_safe . ',';
                $body_p  = 'Grazie per esserti iscritto a ' . $biz_safe . '. Per rispettare la normativa sulla protezione dei dati, conferma il tuo indirizzo email facendo clic sul pulsante qui sotto:';
                $cta     = 'Conferma la mia iscrizione';
                $footer  = 'Se non hai richiesto questo, puoi ignorare questa email in tutta sicurezza.';
                break;

            case 'fr':
                $subject = 'Confirmez votre inscription — ' . $biz_safe;
                $heading = 'Confirmez votre inscription';
                $greeting = 'Bonjour ' . $name_safe . ',';
                $body_p  = 'Merci de vous être inscrit auprès de ' . $biz_safe . '. Pour respecter la réglementation sur la protection des données, veuillez confirmer votre adresse e-mail en cliquant sur le bouton ci-dessous :';
                $cta     = 'Confirmer mon inscription';
                $footer  = 'Si vous n\'êtes pas à l\'origine de cette demande, vous pouvez ignorer cet e-mail en toute sécurité.';
                break;

            case 'de':
                $subject = 'Bestätige dein Abonnement — ' . $biz_safe;
                $heading = 'Bestätige dein Abonnement';
                $greeting = 'Hallo ' . $name_safe . ',';
                $body_p  = 'Danke, dass du dich bei ' . $biz_safe . ' angemeldet hast. Um die Datenschutzbestimmungen einzuhalten, bestätige bitte deine E-Mail-Adresse, indem du auf die Schaltfläche unten klickst:';
                $cta     = 'Mein Abonnement bestätigen';
                $footer  = 'Wenn du das nicht angefordert hast, kannst du diese E-Mail ignorieren.';
                break;

            case 'pt':
                $subject = 'Confirme a sua subscrição — ' . $biz_safe;
                $heading = 'Confirme a sua subscrição';
                $greeting = 'Olá ' . $name_safe . ',';
                $body_p  = 'Obrigado por se inscrever na ' . $biz_safe . '. Para cumprir os regulamentos de proteção de dados, confirme o seu endereço de email clicando no botão abaixo:';
                $cta     = 'Confirmar a minha subscrição';
                $footer  = 'Se não pediu isto, pode ignorar este email com segurança.';
                break;

            case 'en':
            default:
                $lang    = 'en';
                $subject = 'Please Confirm Your Subscription — ' . $biz_safe;
                $heading = 'Please Confirm Your Subscription';
                $greeting = 'Hi ' . $name_safe . ',';
                $body_p  = 'Thank you for signing up with ' . $biz_safe . '. To comply with data protection regulations, please confirm your email address by clicking the button below:';
                $cta     = 'Confirm My Subscription';
                $footer  = 'If you did not request this, you can safely ignore this email.';
                break;
        }

        $body = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:30px;">'
            . '<h2>' . $heading . '</h2>'
            . '<p>' . $greeting . '</p>'
            . '<p>' . $body_p . '</p>'
            . '<p style="text-align:center;margin:25px 0;">'
            . '<a href="' . $url_safe . '" style="background:#4CAF50;color:#fff;padding:12px 30px;border-radius:5px;text-decoration:none;font-size:16px;">' . $cta . '</a>'
            . '</p>'
            . '<p style="font-size:12px;color:#999;">' . $footer . '</p>'
            . '</div>';

        return array('subject' => $subject, 'body' => $body, 'lang' => $lang);
    }

    /**
     * Collapse language codes to a supported branch.
     * All English variants (en, en-gb, en-sc, en-ie, en-wl) share the en branch.
     * Anything unrecognised → en.
     */
    private function _normalize_lang($code)
    {
        $c = strtolower(trim((string) $code));
        if ($c === '' || strpos($c, 'en') === 0) return 'en';
        if (in_array($c, array('es', 'it', 'fr', 'de', 'pt'), true)) return $c;
        // pt-br, es-mx, etc. — strip the regional suffix.
        $head = explode('-', $c)[0];
        if (in_array($head, array('es', 'it', 'fr', 'de', 'pt'), true)) return $head;
        return 'en';
    }

    // ─────────────────────────────────────────────────────────────────
    // SMTP plumbing
    // (Intentionally a private mirror of Sequence_lib's helpers. Two
    //  small copies are cheaper than a premature shared library; the
    //  trigger for factoring this out is a SECOND platform-sent email.)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Look up the funnel owner's SMTP credentials from the settings table.
     * Returns null if no row, missing fields, or decrypt fails.
     */
    private function _get_smtp_creds($user_id)
    {
        $this->CI->db->where(array('user_id' => $user_id, 'meta_type' => 'smtp'));
        $row = $this->CI->db->get('settings')->row();
        if (!$row) return null;

        $this->CI->load->library('encryption');
        $creds = @unserialize($this->CI->encryption->decrypt($row->meta_data));
        if (!is_array($creds)) return null;

        if (empty($creds['smtp_host']) || empty($creds['smtp_username']) || empty($creds['smtp_password'])) {
            return null;
        }
        return $creds;
    }

    /**
     * Send via PHPMailer using the owner's SMTP. Throws on failure.
     */
    private function _send_via_smtp($creds, $to_email, $to_name, $subject, $body)
    {
        require_once('./application/libraries/vendor/autoload.php');

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $creds['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $creds['smtp_username'];
            $mail->Password   = $creds['smtp_password'];
            $mail->SMTPDebug  = 0;
            $mail->Debugoutput = function ($str, $level) { log_message('debug', 'PHPMailer(sys): ' . trim($str)); };

            $port = isset($creds['smtp_port']) ? (int) $creds['smtp_port'] : 587;
            if ($port == 587) {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            }
            $mail->Port = $port;

            // UTF-8 everywhere so accented characters (á, ñ, ü, ç) render correctly
            // in inboxes that default to iso-8859-1.
            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';

            $sender_email = !empty($creds['sender_email']) ? $creds['sender_email'] : $creds['smtp_username'];
            $sender_name  = !empty($creds['sender_name'])  ? $creds['sender_name']  : $sender_email;

            $mail->setFrom($sender_email, $sender_name);
            $mail->addAddress($to_email, $to_name);
            $mail->addReplyTo($sender_email, $sender_name);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
        } catch (\Exception $e) {
            throw new \Exception('SMTP send failed for ' . $to_email . ': ' . $mail->ErrorInfo);
        }
    }
}
