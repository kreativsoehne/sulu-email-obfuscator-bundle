<?php

declare(strict_types=1);

/*
 * This file is part of the kreativsoehne/sulu-email-obfuscator-bundle package.
 *
 * (c) Kreativ&Söhne GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KreativSoehne\SuluEmailObfuscatorBundle\Obfuscator;

/**
 * The browser side as a script tag.
 *
 * Inline and not a file: it is around a kilobyte, and inline it costs no request
 * and no build step - the point of this bundle is that a project installs it and
 * is done. Whoever runs a strict content security policy uses "manual" mode and
 * passes a nonce, or serves the same file from the asset pipeline; it sits in
 * Resources/public and "assets:install" puts it in place.
 */
final class DecoderScript
{
    private const FILE = __DIR__ . '/../Resources/public/email-obfuscator.js';

    private ?string $source = null;

    public function __construct(
        private readonly string $mode = 'auto',
    ) {
    }

    /**
     * Whether the response listener adds the script by itself.
     */
    public function isAutomatic(): bool
    {
        return 'auto' === $this->mode;
    }

    public function tag(?string $nonce = null): string
    {
        if ('none' === $this->mode) {
            return '';
        }

        $attribute = null !== $nonce && '' !== $nonce
            ? \sprintf(' nonce="%s"', \htmlspecialchars($nonce, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'))
            : '';

        return \sprintf('<script%s>%s</script>', $attribute, $this->source());
    }

    private function source(): string
    {
        if (null === $this->source) {
            $source = \file_get_contents(self::FILE);
            $this->source = false === $source ? '' : \trim($source);
        }

        return $this->source;
    }
}
