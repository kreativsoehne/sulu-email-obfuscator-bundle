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

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * The manual way in, for the cases the response listener cannot serve: a template
 * that builds the link itself, and a project that switched the listener off.
 */
final class EmailObfuscatorExtension extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'sulu_obfuscate_email',
                [EmailObfuscatorRuntime::class, 'address'],
            ),
            new TwigFilter(
                'sulu_obfuscate_emails',
                [EmailObfuscatorRuntime::class, 'html'],
                ['is_safe' => ['html']],
            ),
        ];
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'sulu_email_obfuscator_script',
                [EmailObfuscatorRuntime::class, 'script'],
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'sulu_email_obfuscator_attribute',
                [EmailObfuscatorRuntime::class, 'attribute'],
                ['is_safe' => ['html']],
            ),
        ];
    }
}
