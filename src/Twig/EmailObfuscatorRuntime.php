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

namespace KreativSoehne\SuluEmailObfuscatorBundle\Twig;

use KreativSoehne\SuluEmailObfuscatorBundle\Obfuscator\DecoderScript;
use KreativSoehne\SuluEmailObfuscatorBundle\Obfuscator\EmailObfuscator;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\Markup;

final readonly class EmailObfuscatorRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private EmailObfuscator $obfuscator,
        private DecoderScript $script,
        private bool $enabled = true,
    ) {
    }

    /**
     * A single address. The template writes the link around it and marks it with
     * sulu_email_obfuscator_attribute(), otherwise the browser leaves it alone:
     *
     *     <a href="mailto:{{ address|sulu_obfuscate_email }}" {{ sulu_email_obfuscator_attribute() }}>
     *         {{ address|sulu_obfuscate_email }}
     *     </a>
     */
    public function address(?string $address): string
    {
        if (!$this->enabled) {
            return $address ?? '';
        }

        return $this->obfuscator->address($address);
    }

    /**
     * Markup that may hold addresses. Only needed where the response listener is
     * off - with the listener on, this filter is harmless: it marks what it
     * shifts, and marked links are left alone the second time round.
     *
     * The return value is markup and is not escaped afterwards. This filter
     * therefore belongs where "|raw" stood before, never on unchecked input - it
     * neither escapes nor sanitises anything, it only shifts addresses.
     */
    public function html(?string $html): Markup
    {
        $source = $html ?? '';

        return new Markup($this->enabled ? $this->obfuscator->html($source) : $source, 'UTF-8');
    }

    public function script(?string $nonce = null): Markup
    {
        return new Markup($this->enabled ? $this->script->tag($nonce) : '', 'UTF-8');
    }

    public function attribute(): Markup
    {
        return new Markup($this->enabled ? EmailObfuscator::ATTRIBUTE : '', 'UTF-8');
    }
}
