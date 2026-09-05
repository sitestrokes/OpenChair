<?php
/**
 * ui_i18n.php — the ONE code-owned locale catalogue for the FIXED landing-page
 * "chrome" strings that the AI page-fill never touches (they live in the renderer
 * PHP/JS or as hardcoded template HTML, not in funnels.content).
 *
 * Consumed by BOTH sides so translations are never duplicated:
 *   - CI3:            Lang_lib::ui() / ui_map()      (require __DIR__.'/ui_i18n.php')
 *   - custom-domain:  subdomain_data/home.php + thankyou.php  (require this file directly)
 *
 * Contract:
 *   'strings' => token => [ 'en'=>…, 'es'=>…, 'de'=>…, 'pt'=>…, 'it'=>…, 'fr'=>…, 'nb'=>… ]
 *               'en' is BOTH the fallback AND the exact source string used by the
 *               build-time bake pass. en-gb/-sc/-ie/-wl resolve to 'en'.
 *   'bake'    => token => search-context (or '' to use the 'en' value verbatim).
 *               The build-time pass replaces the en substring INSIDE that context,
 *               so short words (Privacy/Terms) only match their real markup slot.
 *
 * NOTE: legal/consent copy here is Claude-drafted and should be human-reviewed
 * before it is relied on for compliance in a given market.
 */
return array(

    'strings' => array(

        // ── Cookie / consent banner (opt-in renderers) ──────────────────────
        'cookie_title'       => array('en'=>'We value your privacy.', 'es'=>'Valoramos tu privacidad.', 'de'=>'Wir schätzen Ihre Privatsphäre.', 'pt'=>'Valorizamos a sua privacidade.', 'it'=>'Teniamo alla tua privacy.', 'fr'=>'Nous respectons votre vie privée.', 'nb'=>'Vi verdsetter personvernet ditt.'),
        'cookie_body'        => array('en'=>'We use cookies to improve your experience and analyze site traffic. Accept to continue.', 'es'=>'Usamos cookies para mejorar tu experiencia y analizar el tráfico del sitio. Acepta para continuar.', 'de'=>'Wir verwenden Cookies, um Ihr Erlebnis zu verbessern und den Website-Traffic zu analysieren. Zum Fortfahren akzeptieren.', 'pt'=>'Usamos cookies para melhorar sua experiência e analisar o tráfego do site. Aceite para continuar.', 'it'=>'Usiamo i cookie per migliorare la tua esperienza e analizzare il traffico del sito. Accetta per continuare.', 'fr'=>'Nous utilisons des cookies pour améliorer votre expérience et analyser le trafic du site. Acceptez pour continuer.', 'nb'=>'Vi bruker informasjonskapsler for å forbedre opplevelsen din og analysere trafikk. Godta for å fortsette.'),
        'cookie_privacy_link'=> array('en'=>'Privacy Policy', 'es'=>'Política de privacidad', 'de'=>'Datenschutzrichtlinie', 'pt'=>'Política de privacidade', 'it'=>'Informativa sulla privacy', 'fr'=>'Politique de confidentialité', 'nb'=>'Personvernerklæring'),
        'cookie_do_not_sell' => array('en'=>'Do Not Sell My Info', 'es'=>'No vender mi información', 'de'=>'Meine Daten nicht verkaufen', 'pt'=>'Não vender minhas informações', 'it'=>'Non vendere le mie informazioni', 'fr'=>'Ne pas vendre mes infos', 'nb'=>'Ikke selg informasjonen min'),
        'cookie_data_rights' => array('en'=>'Data Rights', 'es'=>'Derechos de datos', 'de'=>'Datenrechte', 'pt'=>'Direitos de dados', 'it'=>'Diritti sui dati', 'fr'=>'Droits sur les données', 'nb'=>'Datarettigheter'),
        'cookie_essential'   => array('en'=>'Essential Only', 'es'=>'Solo esenciales', 'de'=>'Nur notwendige', 'pt'=>'Somente essenciais', 'it'=>'Solo essenziali', 'fr'=>'Essentiels uniquement', 'nb'=>'Kun nødvendige'),
        'cookie_accept_all'  => array('en'=>'Accept All', 'es'=>'Aceptar todo', 'de'=>'Alle akzeptieren', 'pt'=>'Aceitar tudo', 'it'=>'Accetta tutto', 'fr'=>'Tout accepter', 'nb'=>'Godta alle'),
        'cookie_gate_error'  => array('en'=>'Please accept our cookie policy below before continuing.', 'es'=>'Acepta nuestra política de cookies a continuación antes de continuar.', 'de'=>'Bitte akzeptieren Sie unten unsere Cookie-Richtlinie, bevor Sie fortfahren.', 'pt'=>'Aceite nossa política de cookies abaixo antes de continuar.', 'it'=>'Accetta la nostra politica sui cookie qui sotto prima di continuare.', 'fr'=>'Veuillez accepter notre politique de cookies ci-dessous avant de continuer.', 'nb'=>'Godta informasjonskapsel-policyen nedenfor før du fortsetter.'),

        // ── Opt-in submit errors ────────────────────────────────────────────
        'err_conn_unavailable'=> array('en'=>'Connection service unavailable — please try again.', 'es'=>'Servicio de conexión no disponible. Inténtalo de nuevo.', 'de'=>'Verbindungsdienst nicht verfügbar. Bitte versuchen Sie es erneut.', 'pt'=>'Serviço de conexão indisponível. Tente novamente.', 'it'=>'Servizio di connessione non disponibile. Riprova.', 'fr'=>'Service de connexion indisponible. Veuillez réessayer.', 'nb'=>'Tilkoblingstjenesten er utilgjengelig. Prøv igjen.'),
        'err_generic'        => array('en'=>'Something went wrong.', 'es'=>'Algo salió mal.', 'de'=>'Etwas ist schiefgelaufen.', 'pt'=>'Algo deu errado.', 'it'=>'Qualcosa è andato storto.', 'fr'=>"Une erreur s'est produite.", 'nb'=>'Noe gikk galt.'),

        // ── Thank-you call widget: controls ─────────────────────────────────
        'call_talk_again'    => array('en'=>'🎙️ Talk again', 'es'=>'🎙️ Hablar de nuevo', 'de'=>'🎙️ Erneut sprechen', 'pt'=>'🎙️ Falar novamente', 'it'=>'🎙️ Parla di nuovo', 'fr'=>'🎙️ Reparler', 'nb'=>'🎙️ Snakk igjen'),
        'call_end'           => array('en'=>'✕ End call', 'es'=>'✕ Finalizar llamada', 'de'=>'✕ Anruf beenden', 'pt'=>'✕ Encerrar chamada', 'it'=>'✕ Termina chiamata', 'fr'=>"✕ Terminer l'appel", 'nb'=>'✕ Avslutt samtale'),
        'call_end_short'     => array('en'=>'✕ End', 'es'=>'✕ Finalizar', 'de'=>'✕ Beenden', 'pt'=>'✕ Encerrar', 'it'=>'✕ Termina', 'fr'=>'✕ Terminer', 'nb'=>'✕ Avslutt'),
        'call_ended_btn'     => array('en'=>'Call ended', 'es'=>'Llamada finalizada', 'de'=>'Anruf beendet', 'pt'=>'Chamada encerrada', 'it'=>'Chiamata terminata', 'fr'=>'Appel terminé', 'nb'=>'Samtale avsluttet'),
        'call_mute'          => array('en'=>'🔇 Mute', 'es'=>'🔇 Silenciar', 'de'=>'🔇 Stumm', 'pt'=>'🔇 Silenciar', 'it'=>'🔇 Muto', 'fr'=>'🔇 Muet', 'nb'=>'🔇 Demp'),
        'call_unmute'        => array('en'=>'🔊 Unmute', 'es'=>'🔊 Activar sonido', 'de'=>'🔊 Ton an', 'pt'=>'🔊 Ativar som', 'it'=>'🔊 Riattiva audio', 'fr'=>'🔊 Activer le son', 'nb'=>'🔊 Slå på lyd'),
        'call_tap_to_talk'   => array('en'=>'🔊 Tap to talk', 'es'=>'🔊 Toca para hablar', 'de'=>'🔊 Zum Sprechen tippen', 'pt'=>'🔊 Toque para falar', 'it'=>'🔊 Tocca per parlare', 'fr'=>'🔊 Touchez pour parler', 'nb'=>'🔊 Trykk for å snakke'),

        // ── Thank-you call widget: status labels ────────────────────────────
        'status_connected'   => array('en'=>'connected', 'es'=>'conectado', 'de'=>'verbunden', 'pt'=>'conectado', 'it'=>'connesso', 'fr'=>'connecté', 'nb'=>'tilkoblet'),
        'status_connecting'  => array('en'=>'connecting…', 'es'=>'conectando…', 'de'=>'verbindet…', 'pt'=>'conectando…', 'it'=>'si sta connettendo…', 'fr'=>'se connecte…', 'nb'=>'kobler til…'),
        'status_call_ended'  => array('en'=>'Call ended', 'es'=>'Llamada finalizada', 'de'=>'Anruf beendet', 'pt'=>'Chamada encerrada', 'it'=>'Chiamata terminata', 'fr'=>'Appel terminé', 'nb'=>'Samtale avsluttet'),
        'status_call_failed' => array('en'=>'Call failed', 'es'=>'Llamada fallida', 'de'=>'Anruf fehlgeschlagen', 'pt'=>'Chamada falhou', 'it'=>'Chiamata non riuscita', 'fr'=>"Échec de l'appel", 'nb'=>'Samtale mislyktes'),
        'status_speaking'    => array('en'=>'speaking…', 'es'=>'hablando…', 'de'=>'spricht…', 'pt'=>'falando…', 'it'=>'sta parlando…', 'fr'=>'parle…', 'nb'=>'snakker…'),
        'status_listening'   => array('en'=>'listening…', 'es'=>'escuchando…', 'de'=>'hört zu…', 'pt'=>'ouvindo…', 'it'=>'sta ascoltando…', 'fr'=>'écoute…', 'nb'=>'lytter…'),
        // The " is " connector between the agent name and the status verb. Set to a
        // bare space where the localized verb is already finite (de/fr/it/nb), and
        // to the copula where it isn't (es/pt): "Alex está hablando…" / "Alex spricht…".
        'status_is'          => array('en'=>' is ', 'es'=>' está ', 'de'=>' ', 'pt'=>' está ', 'it'=>' ', 'fr'=>' ', 'nb'=>' '),

        // ── Thank-you call widget: badge + headline + sub-copy ──────────────
        'badge_live'         => array('en'=>'Live', 'es'=>'En vivo', 'de'=>'Live', 'pt'=>'Ao vivo', 'it'=>'Dal vivo', 'fr'=>'En direct', 'nb'=>'Direkte'),
        'badge_connecting'   => array('en'=>'Connecting', 'es'=>'Conectando', 'de'=>'Verbinde', 'pt'=>'Conectando', 'it'=>'Connessione', 'fr'=>'Connexion', 'nb'=>'Kobler til'),
        'badge_all_set'      => array('en'=>'All set', 'es'=>'Listo', 'de'=>'Fertig', 'pt'=>'Tudo pronto', 'it'=>'Tutto pronto', 'fr'=>"C'est prêt", 'nb'=>'Klart'),
        'badge_got_it'       => array('en'=>'Got it', 'es'=>'Entendido', 'de'=>'Verstanden', 'pt'=>'Entendi', 'it'=>'Fatto', 'fr'=>'Compris', 'nb'=>'Skjønner'),
        'head_connected'     => array('en'=>"You're <span class='a'>connected</span>!", 'es'=>"¡Estás <span class='a'>conectado</span>!", 'de'=>"Sie sind <span class='a'>verbunden</span>!", 'pt'=>"Você está <span class='a'>conectado</span>!", 'it'=>"Sei <span class='a'>connesso</span>!", 'fr'=>"Vous êtes <span class='a'>connecté</span> !", 'nb'=>"Du er <span class='a'>tilkoblet</span>!"),
        'head_all_set'       => array('en'=>'All set! 🙌', 'es'=>'¡Todo listo! 🙌', 'de'=>'Alles erledigt! 🙌', 'pt'=>'Tudo pronto! 🙌', 'it'=>'Tutto pronto! 🙌', 'fr'=>'Tout est prêt ! 🙌', 'nb'=>'Alt klart! 🙌'),
        'head_check_inbox'   => array('en'=>'Check your inbox! 📬', 'es'=>'¡Revisa tu correo! 📬', 'de'=>'Prüfen Sie Ihren Posteingang! 📬', 'pt'=>'Verifique seu e-mail! 📬', 'it'=>'Controlla la tua casella! 📬', 'fr'=>'Consultez votre boîte mail ! 📬', 'nb'=>'Sjekk innboksen din! 📬'),
        'sub_go_ahead'       => array('en'=>'Go ahead and talk — we can hear you. 👋', 'es'=>'Adelante, habla, te escuchamos. 👋', 'de'=>'Sprechen Sie ruhig — wir hören Sie. 👋', 'pt'=>'Pode falar, estamos te ouvindo. 👋', 'it'=>'Vai pure, parla — ti sentiamo. 👋', 'fr'=>'Allez-y, parlez — nous vous entendons. 👋', 'nb'=>'Bare snakk — vi hører deg. 👋'),
        'sub_mic_ready'      => array('en'=>'Mic ready — connecting you now…', 'es'=>'Micrófono listo, conectándote ahora…', 'de'=>'Mikrofon bereit — Sie werden jetzt verbunden…', 'pt'=>'Microfone pronto, conectando você agora…', 'it'=>'Microfono pronto — ti stiamo collegando…', 'fr'=>'Micro prêt — connexion en cours…', 'nb'=>'Mikrofon klar — kobler deg til nå…'),
        'sub_mic_denied'     => array('en'=>'Tap "Allow" on the mic prompt and we will say hi!', 'es'=>'Toca "Permitir" en el aviso del micrófono y te saludaremos.', 'de'=>'Tippen Sie im Mikrofon-Dialog auf „Zulassen“, und wir sagen Hallo!', 'pt'=>'Toque em "Permitir" no aviso do microfone e diremos olá!', 'it'=>'Tocca "Consenti" nella richiesta del microfono e ti saluteremo!', 'fr'=>'Touchez « Autoriser » sur la demande du micro et nous vous dirons bonjour !', 'nb'=>'Trykk «Tillat» på mikrofonforespørselen, så sier vi hei!'),
        'sub_audio_blocked'  => array('en'=>'🔊 Tap anywhere to hear your agent — your browser paused the audio.', 'es'=>'🔊 Toca en cualquier lugar para escuchar a tu agente; tu navegador pausó el audio.', 'de'=>'🔊 Tippen Sie irgendwo, um Ihren Agenten zu hören — Ihr Browser hat den Ton pausiert.', 'pt'=>'🔊 Toque em qualquer lugar para ouvir seu agente; seu navegador pausou o áudio.', 'it'=>'🔊 Tocca ovunque per sentire il tuo agente — il browser ha messo in pausa l\'audio.', 'fr'=>'🔊 Touchez n\'importe où pour entendre votre agent — votre navigateur a mis l\'audio en pause.', 'nb'=>'🔊 Trykk hvor som helst for å høre agenten din — nettleseren satte lyden på pause.'),
        'sub_connecting_now' => array('en'=>'Connecting you now…', 'es'=>'Conectándote ahora…', 'de'=>'Sie werden jetzt verbunden…', 'pt'=>'Conectando você agora…', 'it'=>'Ti stiamo collegando…', 'fr'=>'Connexion en cours…', 'nb'=>'Kobler deg til nå…'),
        'sub_mic_blocked'    => array('en'=>"🎤 Your mic is blocked — we can't hear you. Click the mic icon in the address bar and allow it; we'll hear you the moment it's on.", 'es'=>'🎤 Tu micrófono está bloqueado; no podemos oírte. Haz clic en el icono del micrófono en la barra de direcciones y actívalo; te oiremos en cuanto esté activo.', 'de'=>'🎤 Ihr Mikrofon ist blockiert — wir können Sie nicht hören. Klicken Sie auf das Mikrofon-Symbol in der Adressleiste und erlauben Sie es; wir hören Sie, sobald es an ist.', 'pt'=>'🎤 Seu microfone está bloqueado; não conseguimos te ouvir. Clique no ícone do microfone na barra de endereço e permita; vamos te ouvir assim que estiver ativo.', 'it'=>'🎤 Il tuo microfono è bloccato — non riusciamo a sentirti. Clicca sull\'icona del microfono nella barra degli indirizzi e consentilo; ti sentiremo appena è attivo.', 'fr'=>'🎤 Votre micro est bloqué — nous ne vous entendons pas. Cliquez sur l\'icône du micro dans la barre d\'adresse et autorisez-le ; nous vous entendrons dès qu\'il sera activé.', 'nb'=>'🎤 Mikrofonen din er blokkert — vi hører deg ikke. Klikk på mikrofonikonet i adresselinjen og tillat den; vi hører deg så snart den er på.'),
        'sub_ended_inbox'    => array('en'=>'Your details are in your inbox — enjoy!', 'es'=>'Tus datos están en tu correo. ¡Disfruta!', 'de'=>'Ihre Details sind in Ihrem Posteingang — viel Freude!', 'pt'=>'Seus detalhes estão no seu e-mail. Aproveite!', 'it'=>'I tuoi dati sono nella tua casella — buon divertimento!', 'fr'=>'Vos informations sont dans votre boîte mail — profitez-en !', 'nb'=>'Detaljene dine er i innboksen — kos deg!'),
        'sub_ended_more'     => array('en'=>'All set! Want to talk more? Tap below to pick the conversation back up.', 'es'=>'¡Listo! ¿Quieres seguir hablando? Toca abajo para retomar la conversación.', 'de'=>'Fertig! Möchten Sie weiterreden? Tippen Sie unten, um das Gespräch fortzusetzen.', 'pt'=>'Tudo pronto! Quer conversar mais? Toque abaixo para retomar a conversa.', 'it'=>'Tutto pronto! Vuoi parlare ancora? Tocca qui sotto per riprendere la conversazione.', 'fr'=>'C\'est prêt ! Envie de continuer à parler ? Touchez ci-dessous pour reprendre la conversation.', 'nb'=>'Alt klart! Vil du snakke mer? Trykk nedenfor for å ta opp igjen samtalen.'),
        'sub_error_details'  => array('en'=>'Your details are on the way. ', 'es'=>'Tus datos están en camino. ', 'de'=>'Ihre Details sind unterwegs. ', 'pt'=>'Seus detalhes estão a caminho. ', 'it'=>'I tuoi dati sono in arrivo. ', 'fr'=>'Vos informations arrivent. ', 'nb'=>'Detaljene dine er på vei. '),

        // ── Accessibility skip links (folder-2 templates) ───────────────────
        'skip_main'          => array('en'=>'Skip to main content', 'es'=>'Saltar al contenido principal', 'de'=>'Zum Hauptinhalt springen', 'pt'=>'Pular para o conteúdo principal', 'it'=>'Vai al contenuto principale', 'fr'=>'Aller au contenu principal', 'nb'=>'Hopp til hovedinnhold'),
        'skip_confirm'       => array('en'=>'Skip to confirmation', 'es'=>'Saltar a la confirmación', 'de'=>'Zur Bestätigung springen', 'pt'=>'Pular para a confirmação', 'it'=>'Vai alla conferma', 'fr'=>'Aller à la confirmation', 'nb'=>'Hopp til bekreftelse'),

        // ── Footer legal links ──────────────────────────────────────────────
        'footer_privacy'     => array('en'=>'Privacy', 'es'=>'Privacidad', 'de'=>'Datenschutz', 'pt'=>'Privacidade', 'it'=>'Privacy', 'fr'=>'Confidentialité', 'nb'=>'Personvern'),
        'footer_terms'       => array('en'=>'Terms', 'es'=>'Términos', 'de'=>'AGB', 'pt'=>'Termos', 'it'=>'Termini', 'fr'=>'Conditions', 'nb'=>'Vilkår'),

        // ── Folder-1 form field labels (no jsondata class) ──────────────────
        'label_your_name'    => array('en'=>'Your name', 'es'=>'Tu nombre', 'de'=>'Ihr Name', 'pt'=>'Seu nome', 'it'=>'Il tuo nome', 'fr'=>'Votre nom', 'nb'=>'Navnet ditt'),
        'label_your_email'   => array('en'=>'Your email', 'es'=>'Tu correo', 'de'=>'Ihre E-Mail', 'pt'=>'Seu e-mail', 'it'=>'La tua email', 'fr'=>'Votre e-mail', 'nb'=>'E-posten din'),
        'label_your_phone'   => array('en'=>'Your phone (+1 555 …)', 'es'=>'Tu teléfono (+1 555 …)', 'de'=>'Ihr Telefon (+1 555 …)', 'pt'=>'Seu telefone (+1 555 …)', 'it'=>'Il tuo telefono (+1 555 …)', 'fr'=>'Votre téléphone (+1 555 …)', 'nb'=>'Telefonen din (+1 555 …)'),
        'label_first_name'   => array('en'=>'First name', 'es'=>'Nombre', 'de'=>'Vorname', 'pt'=>'Nome', 'it'=>'Nome', 'fr'=>'Prénom', 'nb'=>'Fornavn'),
        'label_email_access' => array('en'=>'Email for your access link', 'es'=>'Correo para tu enlace de acceso', 'de'=>'E-Mail für Ihren Zugangslink', 'pt'=>'E-mail para seu link de acesso', 'it'=>'Email per il tuo link di accesso', 'fr'=>"E-mail pour votre lien d'accès", 'nb'=>'E-post for tilgangslenken din'),
        'label_phone_reminder'=> array('en'=>'Phone for the reminder', 'es'=>'Teléfono para el recordatorio', 'de'=>'Telefon für die Erinnerung', 'pt'=>'Telefone para o lembrete', 'it'=>'Telefono per il promemoria', 'fr'=>'Téléphone pour le rappel', 'nb'=>'Telefon for påminnelsen'),
        'label_full_name'    => array('en'=>'Full name', 'es'=>'Nombre completo', 'de'=>'Vollständiger Name', 'pt'=>'Nome completo', 'it'=>'Nome completo', 'fr'=>'Nom complet', 'nb'=>'Fullt navn'),
        'label_email_address'=> array('en'=>'Email address', 'es'=>'Correo electrónico', 'de'=>'E-Mail-Adresse', 'pt'=>'Endereço de e-mail', 'it'=>'Indirizzo email', 'fr'=>'Adresse e-mail', 'nb'=>'E-postadresse'),
        'label_direct_line'  => array('en'=>'Direct line', 'es'=>'Línea directa', 'de'=>'Direktnummer', 'pt'=>'Linha direta', 'it'=>'Linea diretta', 'fr'=>'Ligne directe', 'nb'=>'Direktenummer'),
        'label_launch_details'=> array('en'=>'Where to send launch details', 'es'=>'Dónde enviar los detalles del lanzamiento', 'de'=>'Wohin die Launch-Details gesendet werden', 'pt'=>'Onde enviar os detalhes do lançamento', 'it'=>'Dove inviare i dettagli del lancio', 'fr'=>'Où envoyer les détails du lancement', 'nb'=>'Hvor lanseringsdetaljene skal sendes'),
        'label_phone_call'   => array('en'=>'Your phone (for the call)', 'es'=>'Tu teléfono (para la llamada)', 'de'=>'Ihr Telefon (für den Anruf)', 'pt'=>'Seu telefone (para a chamada)', 'it'=>'Il tuo telefono (per la chiamata)', 'fr'=>"Votre téléphone (pour l'appel)", 'nb'=>'Telefonen din (for samtalen)'),
    ),

    // Tokens applied at BUILD time to the baked template HTML. Value = the exact
    // markup context to search in ('' => use the 'en' string verbatim). Short words
    // (Privacy/Terms/" is ") carry context so they only hit their real slot.
    'bake' => array(
        'skip_main'          => '',
        'skip_confirm'       => '',
        'footer_privacy'     => '>Privacy<',
        'footer_terms'       => '>Terms<',
        'call_mute'          => '',
        'call_end'           => '',
        'call_end_short'     => '',
        'call_tap_to_talk'   => '',
        'status_speaking'    => '',
        'status_connecting'  => '',
        'status_listening'   => '',
        'status_is'          => '> is <',
        'label_your_name'    => '>Your name<',
        'label_your_email'   => '>Your email<',
        'label_your_phone'   => '',
        'label_first_name'   => '>First name<',
        'label_email_access' => '',
        'label_phone_reminder'=> '',
        'label_full_name'    => '>Full name<',
        'label_email_address'=> '>Email address<',
        'label_direct_line'  => '>Direct line<',
        'label_launch_details'=> '',
        'label_phone_call'   => '',
    ),
);
