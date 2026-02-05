<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class RichTextSanitizer
{
    /**
     * @var array<string, array<int, string>>
     */
    protected array $allowedAttributes = [
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt'],
        'p' => ['style'],
        'div' => ['style'],
        'span' => ['style'],
        'h1' => ['style'],
        'h2' => ['style'],
        'h3' => ['style'],
    ];

    /**
     * @var array<int, string>
     */
    protected array $allowedTags = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'a', 'img', 'h1', 'h2', 'h3', 'div', 'span',
    ];

    public static function sanitize(?string $html): ?string
    {
        $value = trim((string) $html);
        if ($value === '') {
            return null;
        }

        return (new self())->clean($value);
    }

    protected function clean(string $html): ?string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        $wrapped = '<div id="editor-root">'.$html.'</div>';
        $encoded = function_exists('mb_convert_encoding')
            ? mb_convert_encoding($wrapped, 'HTML-ENTITIES', 'UTF-8')
            : $wrapped;

        $dom->loadHTML($encoded, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        /** @var DOMElement|null $root */
        $root = (new DOMXPath($dom))->query('//*[@id="editor-root"]')->item(0);
        if (! $root) {
            libxml_clear_errors();
            return null;
        }

        $this->sanitizeNode($root);

        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        libxml_clear_errors();

        $result = trim($result);

        return $result !== '' ? $result : null;
    }

    protected function sanitizeNode(DOMNode $node): void
    {
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (! in_array($tag, $this->allowedTags, true)) {
                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed'], true)) {
                        $node->removeChild($child);
                        continue;
                    }

                    $this->unwrapNode($child);
                    continue;
                }

                $this->sanitizeAttributes($child, $tag);
            }

            $this->sanitizeNode($child);
        }
    }

    protected function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowed = $this->allowedAttributes[$tag] ?? [];
        $toRemove = [];

        foreach ($element->attributes as $attribute) {
            $name = strtolower($attribute->name);
            if (! in_array($name, $allowed, true)) {
                $toRemove[] = $name;
            }
        }

        foreach ($toRemove as $name) {
            $element->removeAttribute($name);
        }

        if ($element->hasAttribute('href')) {
            $href = trim((string) $element->getAttribute('href'));
            if (! $this->isSafeUrl($href)) {
                $element->removeAttribute('href');
            }
        }

        if ($element->hasAttribute('src')) {
            $src = trim((string) $element->getAttribute('src'));
            if (! $this->isSafeUrl($src, true)) {
                $element->removeAttribute('src');
            }
        }

        if ($element->hasAttribute('style')) {
            $style = trim((string) $element->getAttribute('style'));
            $normalized = $this->sanitizeStyle($style);
            if ($normalized === null) {
                $element->removeAttribute('style');
            } else {
                $element->setAttribute('style', $normalized);
            }
        }

        if ($tag === 'a') {
            if ($element->getAttribute('target') === '_blank') {
                $element->setAttribute('rel', 'noopener noreferrer');
            } else {
                $element->removeAttribute('target');
                $element->removeAttribute('rel');
            }
        }

        if ($tag === 'img' && ! $element->hasAttribute('src')) {
            $element->parentNode?->removeChild($element);
        }
    }

    protected function unwrapNode(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (! $parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    protected function isSafeUrl(string $url, bool $allowDataImage = false): bool
    {
        if ($url === '') {
            return false;
        }

        if ($allowDataImage && preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $url)) {
            return true;
        }

        if (str_starts_with($url, '/')) {
            return true;
        }

        $parts = parse_url($url);
        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        return in_array($scheme, ['http', 'https'], true);
    }

    protected function sanitizeStyle(string $style): ?string
    {
        if (! preg_match('/text-align\s*:\s*(left|center|right)\s*;?/i', $style, $matches)) {
            return null;
        }

        return 'text-align: '.strtolower($matches[1]).';';
    }
}
