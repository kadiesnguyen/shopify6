<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class RichTextSanitizer
{
    /** @var list<string> */
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'h1', 'h2', 'h3',
        'ul', 'ol', 'li', 'a', 'img', 'span', 'blockquote',
    ];

    public function clean(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"><div id="rich-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();

        $root = $document->getElementById('rich-root');

        if (! $root) {
            return null;
        }

        $this->sanitizeChildren($root);

        $result = '';

        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        $result = trim($result);

        return $result !== '' ? $result : null;
    }

    private function sanitizeChildren(DOMNode $parent): void
    {
        /** @var list<DOMNode> $children */
        $children = [];

        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            /** @var DOMElement $child */
            $tag = strtolower($child->nodeName);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                while ($child->firstChild) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                $parent->removeChild($child);

                continue;
            }

            $this->sanitizeAttributes($child);
            $this->sanitizeChildren($child);
        }
    }

    private function sanitizeAttributes(DOMElement $element): void
    {
        $allowed = match (strtolower($element->tagName)) {
            'a' => ['href', 'target', 'rel'],
            'img' => ['src', 'alt', 'width', 'height', 'class'],
            'span' => ['style', 'class'],
            default => ['class', 'style'],
        };

        /** @var list<\DOMAttr> $attributes */
        $attributes = [];

        foreach ($element->attributes as $attribute) {
            $attributes[] = $attribute;
        }

        foreach ($attributes as $attribute) {
            if (! in_array($attribute->name, $allowed, true)) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if ($attribute->name === 'href' && ! $this->isSafeUrl($attribute->value)) {
                $element->removeAttribute('href');
            }

            if ($attribute->name === 'src' && ! $this->isSafeImageSrc($attribute->value)) {
                $element->removeAttribute('src');
            }

            if ($attribute->name === 'style' && ! $this->isSafeStyle($attribute->value)) {
                $element->removeAttribute('style');
            }
        }
    }

    private function isSafeUrl(string $url): bool
    {
        return str_starts_with($url, '/')
            || str_starts_with($url, '#')
            || filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    private function isSafeImageSrc(string $src): bool
    {
        if (str_starts_with($src, '/storage/')
            || str_starts_with($src, '/images/')
            || str_starts_with($src, 'storage/')
            || str_starts_with($src, 'images/')) {
            return true;
        }

        if (! filter_var($src, FILTER_VALIDATE_URL)) {
            return false;
        }

        return str_contains($src, '/storage/')
            || str_contains($src, '/images/');
    }

    private function isSafeStyle(string $style): bool
    {
        return (bool) preg_match(
            '/^(?:\s*(?:color|background-color|font-size|text-align|font-weight|font-style|text-decoration)\s*:\s*[^;]+;?\s*)+$/i',
            $style,
        );
    }
}
