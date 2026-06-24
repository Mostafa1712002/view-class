<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Dependency-free HTML sanitizer for user-authored rich text (e.g. announcement
 * bodies, mail bodies). Strips dangerous tags (script/iframe/object/embed/style/…),
 * all event-handler (on*) attributes, and javascript:/data: URLs, while keeping a
 * whitelist of safe formatting tags + attributes. Use on WRITE so stored values are
 * already safe; rendering them with {!! !!} is then safe.
 */
class HtmlSanitizer
{
    /** Allowed tags → allowed attributes for each. */
    private const ALLOWED = [
        'p' => ['style'], 'br' => [], 'span' => ['style'], 'div' => ['style'],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'sub' => [], 'sup' => [],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'ul' => [], 'ol' => ['start'], 'li' => [],
        'blockquote' => [], 'pre' => [], 'code' => [], 'hr' => [],
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'table' => ['style', 'border', 'cellpadding', 'cellspacing', 'width'],
        'thead' => [], 'tbody' => [], 'tfoot' => [],
        'tr' => ['style'],
        'th' => ['colspan', 'rowspan', 'style', 'scope'],
        'td' => ['colspan', 'rowspan', 'style'],
        'caption' => [], 'figure' => [], 'figcaption' => [],
    ];

    /** Attributes holding URLs that must be scheme-checked. */
    private const URL_ATTRS = ['href', 'src'];

    /** Only these CSS properties survive in a style="" attribute. */
    private const ALLOWED_STYLE_PROPS = [
        'color', 'background-color', 'text-align', 'font-weight', 'font-style',
        'text-decoration', 'direction', 'margin', 'padding', 'font-size',
        'font-family', 'text-indent', 'line-height', 'width', 'height',
        'border', 'border-collapse', 'vertical-align',
    ];

    public static function clean(?string $html): string
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        // Wrap so we can parse a fragment; force UTF-8.
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="__root__">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $dom->getElementById('__root__');
        if (! $root) {
            return '';
        }

        self::sanitizeNode($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        // Iterate over a snapshot — we mutate the tree while walking.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->nodeName);

                if (! array_key_exists($tag, self::ALLOWED)) {
                    // Disallowed tag: drop the element entirely (and its subtree for
                    // dangerous containers; otherwise unwrap its text children).
                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'link', 'meta'], true)) {
                        $child->parentNode->removeChild($child);
                    } else {
                        self::sanitizeNode($child);
                        while ($child->firstChild) {
                            $child->parentNode->insertBefore($child->firstChild, $child);
                        }
                        $child->parentNode->removeChild($child);
                    }
                    continue;
                }

                self::cleanAttributes($child, $tag);
                self::sanitizeNode($child);
            } elseif (! ($child instanceof \DOMText) && ! ($child instanceof \DOMCdataSection)) {
                // Comments, processing instructions, etc. → drop.
                $child->parentNode->removeChild($child);
            }
        }
    }

    private static function cleanAttributes(DOMElement $el, string $tag): void
    {
        $allowedAttrs = self::ALLOWED[$tag];
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            $value = $attr->nodeValue;

            // Always strip event handlers and anything not whitelisted for this tag.
            if (str_starts_with($name, 'on') || ! in_array($name, $allowedAttrs, true)) {
                $el->removeAttribute($attr->nodeName);
                continue;
            }

            if (in_array($name, self::URL_ATTRS, true) && ! self::safeUrl($value)) {
                $el->removeAttribute($attr->nodeName);
                continue;
            }

            if ($name === 'style') {
                $clean = self::cleanStyle($value);
                if ($clean === '') {
                    $el->removeAttribute('style');
                } else {
                    $el->setAttribute('style', $clean);
                }
            }
        }

        // Harden links that open a new tab.
        if ($tag === 'a' && $el->getAttribute('target') === '_blank') {
            $el->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function safeUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        // Reject anything with a dangerous scheme (javascript:, data:, vbscript:, …).
        if (preg_match('/^\s*(javascript|data|vbscript|file)\s*:/i', $url)) {
            return false;
        }
        // Allow http(s), mailto, tel, protocol-relative, and same-origin relative URLs.
        return (bool) preg_match('#^(https?:|mailto:|tel:|//|/|\#|[^:]+$)#i', $url);
    }

    private static function cleanStyle(string $style): string
    {
        $out = [];
        foreach (explode(';', $style) as $decl) {
            if (! str_contains($decl, ':')) {
                continue;
            }
            [$prop, $val] = array_map('trim', explode(':', $decl, 2));
            $prop = strtolower($prop);
            if (! in_array($prop, self::ALLOWED_STYLE_PROPS, true)) {
                continue;
            }
            // Drop values containing url()/expression()/javascript — CSS-based vectors.
            if (preg_match('/url\s*\(|expression\s*\(|javascript:/i', $val)) {
                continue;
            }
            $out[] = $prop . ':' . $val;
        }

        return implode(';', $out);
    }
}
