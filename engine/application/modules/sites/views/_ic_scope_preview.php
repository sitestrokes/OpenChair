<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * SCOPE A PREVIEW'S CSS — fix cross-page style bleed in the page editor.
 *
 * The editor renders BOTH the Funnel page (#templateEditor1) and the Thank-You
 * page (#templateEditor2) into one DOM. Each page ships its OWN full <style>
 * blocks — including document-global rules like  :root{--bg:#fff}  and bare
 * class rules like  .panel{...} . Because both pages share one document, those
 * globals leak across pages: the Thank-You template's light :root + generic
 * .panel rule turn the Funnel page's (dark) CLAIM_FORM panel white.
 *
 * ic_scope_preview_html($html, $scope) rewrites every <style> rule in $html so
 * it only applies under $scope (e.g. "#templateEditor1"):
 *   - :root / html / body selectors    →  the scope element itself
 *   - @keyframes / @font-face / @import →  left untouched (global by nature)
 *   - every other selector             →  prefixed with "$scope "
 * Non-<style> markup (the page body, the funnelrh/funnelrb wrappers) is returned
 * verbatim. This is editor-only; the live page renders ONE page and is untouched.
 *
 * It is deliberately a light-touch transform (no full CSS parser): it splits on
 * top-level rule boundaries and prefixes selector lists. Media queries are
 * recursed into so their inner rules are scoped too.
 */
if (!function_exists('ic_scope_css')) {
    /**
     * Scope a raw CSS string under $scope. Handles nested @media/@supports.
     */
    function ic_scope_css($css, $scope)
    {
        $out = '';
        $i = 0; $n = strlen($css);
        while ($i < $n) {
            // Skip whitespace.
            if (ctype_space($css[$i])) { $out .= $css[$i]; $i++; continue; }

            // Comment — copy through.
            if ($css[$i] === '/' && $i + 1 < $n && $css[$i + 1] === '*') {
                $end = strpos($css, '*/', $i + 2);
                if ($end === false) { $out .= substr($css, $i); break; }
                $out .= substr($css, $i, $end - $i + 2);
                $i = $end + 2;
                continue;
            }

            // At-rule.
            if ($css[$i] === '@') {
                // Read the at-keyword + prelude up to '{' or ';'.
                $j = $i;
                while ($j < $n && $css[$j] !== '{' && $css[$j] !== ';') { $j++; }
                $prelude = substr($css, $i, $j - $i);
                $atword  = strtolower(preg_replace('/^@([a-z-]+).*/is', '$1', trim($prelude)));

                if ($j < $n && $css[$j] === ';') {
                    // @import / @charset / @namespace — copy as-is.
                    $out .= substr($css, $i, $j - $i + 1);
                    $i = $j + 1;
                    continue;
                }
                if ($j >= $n) { $out .= substr($css, $i); break; }

                // Block at-rule: find its matching close brace.
                $block = ic_css_read_block($css, $j); // returns [innerStart, innerEnd, afterPos]
                $inner = substr($css, $block[0], $block[1] - $block[0]);

                if (in_array($atword, array('keyframes', 'font-face', 'page', 'counter-style', 'font-feature-values'), true)) {
                    // Global-by-nature — keep inner rules untouched.
                    $out .= $prelude . '{' . $inner . '}';
                } else {
                    // @media / @supports / @container — scope the inner rules.
                    $out .= $prelude . '{' . ic_scope_css($inner, $scope) . '}';
                }
                $i = $block[2];
                continue;
            }

            // Normal rule: read selector list up to '{', then its block.
            $brace = strpos($css, '{', $i);
            if ($brace === false) { $out .= substr($css, $i); break; }
            $selectors = substr($css, $i, $brace - $i);
            $block = ic_css_read_block($css, $brace);
            $body  = substr($css, $block[0], $block[1] - $block[0]);

            $out .= ic_scope_selector_list($selectors, $scope) . '{' . $body . '}';
            $i = $block[2];
        }
        return $out;
    }

    /**
     * Given the position of an opening '{' in $css, return
     * [innerStart, innerEnd, afterClosePos] honoring nested braces.
     */
    function ic_css_read_block($css, $openPos)
    {
        $n = strlen($css);
        $depth = 0;
        $i = $openPos;
        $innerStart = $openPos + 1;
        for (; $i < $n; $i++) {
            if ($css[$i] === '{') { $depth++; }
            elseif ($css[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return array($innerStart, $i, $i + 1);
                }
            }
        }
        return array($innerStart, $n, $n);
    }

    /**
     * Prefix each selector in a comma list with $scope. :root/html/body collapse
     * to the scope element itself (the page wrapper carries the page's vars).
     */
    function ic_scope_selector_list($selectors, $scope)
    {
        $parts = explode(',', $selectors);
        foreach ($parts as &$sel) {
            $trim = trim($sel);
            if ($trim === '') { continue; }
            // Root-ish selectors → the scope element (so :root{--x} vars live on it).
            if (preg_match('/^(:root|html|body)\b/i', $trim)) {
                $rest = preg_replace('/^(:root|html|body)/i', '', $trim);
                $sel  = $scope . $rest;
            } else {
                $sel = $scope . ' ' . $trim;
            }
        }
        unset($sel);
        return implode(',', $parts);
    }
}

if (!function_exists('ic_scope_preview_html')) {
    /**
     * Rewrite every <style> block inside $html so its rules are scoped under
     * $scope. Everything else is returned verbatim.
     */
    function ic_scope_preview_html($html, $scope)
    {
        return preg_replace_callback(
            '#(<style\b[^>]*>)(.*?)(</style>)#is',
            function ($m) use ($scope) {
                return $m[1] . ic_scope_css($m[2], $scope) . $m[3];
            },
            $html
        );
    }
}
