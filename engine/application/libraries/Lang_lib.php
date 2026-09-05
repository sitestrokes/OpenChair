<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Lang_lib — single source of truth for funnel language metadata.
 *
 * Used by:
 *   - Funnel wizard (dropdown population, validation)
 *   - AI generation prompts (prompt_directive prepended to every site)
 *   - Realtime voice bridges (Polly voice, Gemini speech_config, ElevenLabs hint)
 *   - Public landing page <html lang="..."> attribute
 *
 * Loaded lazily where needed; not autoloaded.
 */
class Lang_lib {

    /**
     * Per-language metadata. Keep keys lowercase ISO-639-1 short codes.
     *   label   : human label injected into prompt directive
     *   iso     : BCP-47 locale (used by Twilio Polly + JS toLocaleDateString)
     *   polly   : Twilio <Say> voice for legacy TwiML
     *   gemini  : Gemini Live speech_config.language_code
     *   eleven  : ElevenLabs convai language hint
     */
    private static $LANGS = [
        'en'    => ['label' => 'English (US)',         'iso' => 'en-US', 'polly' => 'Joanna',  'gemini' => 'en-US', 'eleven' => 'en'],
        'en-gb' => ['label' => 'English (British)',    'iso' => 'en-GB', 'polly' => 'Amy',     'gemini' => 'en-GB', 'eleven' => 'en'],
        'en-sc' => ['label' => 'English (Scottish)',   'iso' => 'en-GB', 'polly' => 'Brian',   'gemini' => 'en-GB', 'eleven' => 'en'],
        'en-ie' => ['label' => 'English (Irish)',      'iso' => 'en-IE', 'polly' => 'Niamh',   'gemini' => 'en-GB', 'eleven' => 'en'],
        'en-wl' => ['label' => 'English (Welsh)',      'iso' => 'en-GB', 'polly' => 'Geraint', 'gemini' => 'en-GB', 'eleven' => 'en'],
        'es'    => ['label' => 'Spanish',              'iso' => 'es-US', 'polly' => 'Lupe',    'gemini' => 'es-US', 'eleven' => 'es'],
        // 'es-es' => ['label' => 'Spanish (Spain)', 'iso' => 'es-ES', 'polly' => 'Lucia', 'gemini' => 'es-ES', 'eleven' => 'es'],
        'de'    => ['label' => 'German',               'iso' => 'de-DE', 'polly' => 'Vicki',   'gemini' => 'de-DE', 'eleven' => 'de'],
        'pt'    => ['label' => 'Portuguese',           'iso' => 'pt-BR', 'polly' => 'Camila',  'gemini' => 'pt-BR', 'eleven' => 'pt'],
        'it'    => ['label' => 'Italian',              'iso' => 'it-IT', 'polly' => 'Bianca',  'gemini' => 'it-IT', 'eleven' => 'it'],
        'fr'    => ['label' => 'French',               'iso' => 'fr-FR', 'polly' => 'Lea',     'gemini' => 'fr-FR', 'eleven' => 'fr'],
        'nb'    => ['label' => 'Norwegian (Bokmål)',   'iso' => 'nb-NO', 'polly' => 'Liv',     'gemini' => 'nb-NO', 'eleven' => 'nb'],
        // 'hi' => ['label' => 'Hindi',               'iso' => 'hi-IN', 'polly' => 'Aditi',  'gemini' => 'hi-IN', 'eleven' => 'hi'],
    ];

    public function supported() {
        return self::$LANGS;
    }

    public function get($code) {
        $code = strtolower((string)$code);
        return isset(self::$LANGS[$code]) ? self::$LANGS[$code] : self::$LANGS['en'];
    }

    public function label($code) {
        $info = $this->get($code);
        return $info['label'];
    }

    /**
     * Validate against supported set, falling back to 'en' on unknown.
     * Use everywhere user input lands (POST body, GET param, etc.).
     */
    public function normalize($code) {
        $code = strtolower(trim((string)$code));
        return isset(self::$LANGS[$code]) ? $code : 'en';
    }

    /**
     * The directive prepended to every AI generation prompt.
     * Idempotent: returns '' for 'en' so existing English prompts pay zero
     * token cost and stay byte-identical to pre-rollout behavior.
     */
    public function prompt_directive($code) {
        $code = $this->normalize($code);
        if ($code === 'en') return '';
        $info = $this->get($code);
        return "LANGUAGE: Generate ALL user-facing text in {$info['label']} ({$code}). "
             . "Keep JSON keys in English. Keep brand and business proper nouns in their original form. "
             . "Localize idioms — do not translate word-for-word.\n\n";
    }

    // Map legacy pitch_lang codes (settings.meta_type='lang') to ISO-639-1.
    private static $LEGACY_MAP = [
        'sp' => 'es', // Spanish
        'gr' => 'de', // German
        'pg' => 'pt', // Portuguese
    ];

    /**
     * Look up the user's saved default language from the existing settings
     * row (meta_type='lang'). Returns a normalized ISO code so the wizard
     * can pre-select it. Returns 'en' if not set.
     */
    // ── UI locale catalogue (fixed landing-page "chrome" strings) ───────────
    // The AI page-fill never touches these (they live in renderer PHP/JS or as
    // hardcoded template HTML). One shared source: application/libraries/ui_i18n.php,
    // read here AND require()d directly by the CI-less subdomain_data renderers.

    private static $UI = null;

    /** Lazy-load + cache the shared catalogue (['strings'=>…, 'bake'=>…]). */
    private static function ui_catalogue() {
        if (self::$UI === null) {
            $file = __DIR__ . '/ui_i18n.php';
            $data = is_file($file) ? require $file : array();
            self::$UI = is_array($data) ? $data : array();
        }
        return self::$UI;
    }

    /** Map any funnel code to a catalogue key: en-* variants collapse to 'en'. */
    private function ui_lang($code) {
        $code = strtolower(trim((string)$code));
        if ($code === '' || strpos($code, 'en') === 0) return 'en';
        $cat = self::ui_catalogue();
        $any = isset($cat['strings']) ? reset($cat['strings']) : array();
        return (is_array($any) && isset($any[$code])) ? $code : 'en';
    }

    /**
     * One fixed UI string in the given language. Falls back to English, then to
     * the token itself. Use for renderer-injected strings (call widget, cookie
     * banner, submit errors) keyed by stable token.
     */
    public function ui($token, $code) {
        $cat  = self::ui_catalogue();
        $lang = $this->ui_lang($code);
        if (isset($cat['strings'][$token][$lang])) return $cat['strings'][$token][$lang];
        if (isset($cat['strings'][$token]['en']))  return $cat['strings'][$token]['en'];
        return (string) $token;
    }

    /** The whole token=>string map for a language (for JS injection via json_encode). */
    public function ui_map($code) {
        $cat  = self::ui_catalogue();
        $lang = $this->ui_lang($code);
        $out  = array();
        if (!empty($cat['strings'])) {
            foreach ($cat['strings'] as $token => $langs) {
                $out[$token] = isset($langs[$lang]) ? $langs[$lang] : (isset($langs['en']) ? $langs['en'] : $token);
            }
        }
        return $out;
    }

    /**
     * Build-time find=>replace pairs for the baked template HTML. Empty for
     * English (byte-identical). For each bake token: search its markup context
     * (or the raw English string) and swap the English substring for the localized
     * one — so short words only hit their real slot (e.g. `>Privacy<`).
     */
    public function ui_bake_pairs($code) {
        $lang = $this->ui_lang($code);
        if ($lang === 'en') return array();
        $cat = self::ui_catalogue();
        $pairs = array();
        if (empty($cat['bake'])) return $pairs;
        foreach ($cat['bake'] as $token => $context) {
            if (!isset($cat['strings'][$token]['en'], $cat['strings'][$token][$lang])) continue;
            $en  = $cat['strings'][$token]['en'];
            $loc = $cat['strings'][$token][$lang];
            if ($en === $loc) continue;
            $find = ($context !== '') ? $context : $en;
            $repl = str_replace($en, $loc, $find);
            if ($find !== '' && $find !== $repl) { $pairs[$find] = $repl; }
        }
        return $pairs;
    }

    public function user_default($user_id) {
        if (empty($user_id)) return 'en';
        $CI =& get_instance();
        $row = $CI->db->where(['user_id' => $user_id, 'meta_type' => 'lang'])->get('settings')->row();
        if (!$row || empty($row->meta_data)) return 'en';

        $blob = $row->meta_data;
        // Existing rows are stored as either plaintext serialized OR encrypted-then-serialized.
        // Try unserialize first; if that fails, try decrypt + unserialize.
        $parsed = @unserialize($blob);
        if ($parsed === false) {
            try {
                $CI->load->library('encryption');
                $parsed = @unserialize($CI->encryption->decrypt($blob));
            } catch (Exception $e) {
                return 'en';
            }
        }
        if (!is_array($parsed) || empty($parsed['pitch_lang'])) return 'en';

        $code = strtolower(trim((string)$parsed['pitch_lang']));
        if (isset(self::$LEGACY_MAP[$code])) $code = self::$LEGACY_MAP[$code];
        return $this->normalize($code);
    }
}
