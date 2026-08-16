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
 * Shifts e-mail addresses by ROT13 so they leave the server unreadable.
 *
 * Obfuscated links carry a marker attribute. It serves two purposes: the browser
 * script knows what to turn back, and this class knows what it has already seen -
 * running the obfuscator twice over the same markup is therefore harmless.
 *
 * This is deterrence, not protection. Anyone who knows ROT13 has the address; the
 * point is the simple harvester that grabs whatever looks like an address in the
 * source. The price is JavaScript: without it, the address stays unreadable.
 */
final readonly class EmailObfuscator
{
    /**
     * The marker on an obfuscated link. Not configurable on purpose: the browser
     * script carries the same name, and two places to change it is one too many.
     */
    public const ATTRIBUTE = 'data-obfuscated-email';

    /**
     * An address in running text. Deliberately narrow - text that does not match
     * simply stays as it is, while a wrongly detected "address" would break the
     * page.
     */
    private const ADDRESS = '[\w.+-]+@[\w-]+(?:\.[\w-]+)+';

    /**
     * One pass over the markup. The order of the branches is the statement:
     * scripts, styles and every tag are consumed as a whole and passed through
     * untouched, so an address inside an attribute is never rewritten. Only what
     * is left over is text, and only there does the last branch apply.
     *
     * Leaving scripts alone also keeps structured data (JSON-LD) intact. An
     * address repeated in a FAQPage or Organization block therefore stays
     * readable - see the README.
     */
    private const PATTERN = '~
          <script\b[^>]*>.*?</script>
        | <style\b[^>]*>.*?</style>
        | <a\b(?<before>[^>]*?)href=(?<quote>["\'])mailto:(?<target>[^"\']*)(?P=quote)(?<after>[^>]*)>(?<label>.*?)</a>
        | <[^>]+>
        | (?<plain>' . self::ADDRESS . ')
    ~isx';

    public function __construct(
        /**
         * Whether an address written as plain text becomes a link. Off means such
         * an address is left readable, because turning it into a link is the only
         * way to obfuscate it.
         */
        private bool $linkPlainAddresses = true,
    ) {
    }

    /**
     * A single address, for a template that builds the link itself.
     */
    public function address(?string $address): string
    {
        return \str_rot13($address ?? '');
    }

    /**
     * Markup: rewrites mailto links, and turns plain addresses into links.
     */
    public function html(?string $html): string
    {
        $source = $html ?? '';

        if (!\str_contains($source, '@')) {
            return $source;
        }

        return \preg_replace_callback(self::PATTERN, $this->replace(...), $source) ?? $source;
    }

    /**
     * @param array<int|string, string> $matches
     */
    private function replace(array $matches): string
    {
        if ('' !== ($matches['plain'] ?? '')) {
            if (!$this->linkPlainAddresses) {
                return $matches[0];
            }

            return \sprintf(
                '<a href="mailto:%1$s" %2$s>%1$s</a>',
                \str_rot13($matches['plain']),
                self::ATTRIBUTE,
            );
        }

        if ('' !== ($matches['target'] ?? '')) {
            return $this->link($matches);
        }

        return $matches[0];
    }

    /**
     * @param array<int|string, string> $matches
     */
    private function link(array $matches): string
    {
        $attributes = $matches['before'] . $matches['after'];

        // Already shifted: a second pass would make the address readable again.
        if (\str_contains($attributes, self::ATTRIBUTE)) {
            return $matches[0];
        }

        // A subject behind the question mark is not part of the address and stays
        // readable.
        $target = $matches['target'];
        $query = '';
        $separator = \strpos($target, '?');

        if (false !== $separator) {
            $query = \substr($target, $separator);
            $target = \substr($target, 0, $separator);
        }

        // Only shift the label if it is an address itself. A word ("write to us")
        // stays readable, and the browser script follows the same rule.
        $label = $matches['label'];

        if (1 === \preg_match('~^\s*' . self::ADDRESS . '\s*$~i', $label)) {
            $label = \str_rot13(\trim($label));
        }

        return \sprintf(
            '<a%s href=%smailto:%s%s%s%s %s>%s</a>',
            \rtrim($matches['before']),
            $matches['quote'],
            \str_rot13($target),
            $query,
            $matches['quote'],
            \rtrim($matches['after']),
            self::ATTRIBUTE,
            $label,
        );
    }
}
