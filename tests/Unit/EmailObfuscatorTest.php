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

namespace KreativSoehne\SuluEmailObfuscatorBundle\Tests\Unit;

use KreativSoehne\SuluEmailObfuscatorBundle\Obfuscator\EmailObfuscator;
use PHPUnit\Framework\TestCase;

final class EmailObfuscatorTest extends TestCase
{
    private EmailObfuscator $obfuscator;

    protected function setUp(): void
    {
        $this->obfuscator = new EmailObfuscator();
    }

    public function testAddressIsShifted(): void
    {
        $this->assertSame('vasb@rknzcyr.pbz', $this->obfuscator->address('info@example.com'));
    }

    public function testEmptyAddressStaysEmpty(): void
    {
        $this->assertSame('', $this->obfuscator->address(null));
    }

    public function testPlainAddressBecomesAnObfuscatedLink(): void
    {
        $this->assertSame('<p>Write to <a href="mailto:vasb@rknzcyr.pbz" data-obfuscated-email>vasb@rknzcyr.pbz</a>.</p>', $this->obfuscator->html('<p>Write to info@example.com.</p>'));
    }

    public function testPlainAddressStaysUntouchedWhenLinkingIsOff(): void
    {
        $obfuscator = new EmailObfuscator(linkPlainAddresses: false);

        $this->assertSame('<p>Write to info@example.com.</p>', $obfuscator->html('<p>Write to info@example.com.</p>'));
    }

    public function testMailtoLinkAndItsLabelAreShifted(): void
    {
        $this->assertSame('<a href="mailto:vasb@rknzcyr.pbz" data-obfuscated-email>vasb@rknzcyr.pbz</a>', $this->obfuscator->html('<a href="mailto:info@example.com">info@example.com</a>'));
    }

    /**
     * A label that is not an address stays readable - the browser script follows
     * the same rule and leaves it alone as well.
     */
    public function testWordLabelStaysReadable(): void
    {
        $this->assertSame('<a class="btn" href="mailto:vasb@rknzcyr.pbz" data-obfuscated-email>Write to us</a>', $this->obfuscator->html('<a class="btn" href="mailto:info@example.com">Write to us</a>'));
    }

    /**
     * Icon and span around the address stay; only the address in the text is
     * shifted, so the visible label is no longer readable in the source.
     */
    public function testAddressInsideMarkupOfTheLabelIsShifted(): void
    {
        $this->assertSame(
            '<a class="card-link" href="mailto:vasb@rknzcyr.pbz" data-obfuscated-email><i class="icon" aria-hidden="true"></i><span class="label">vasb@rknzcyr.pbz</span></a>',
            $this->obfuscator->html('<a class="card-link" href="mailto:info@example.com"><i class="icon" aria-hidden="true"></i><span class="label">info@example.com</span></a>'),
        );
    }

    public function testWordInsideMarkupOfTheLabelStaysReadable(): void
    {
        $this->assertSame(
            '<a href="mailto:vasb@rknzcyr.pbz" data-obfuscated-email><span>Write to us</span></a>',
            $this->obfuscator->html('<a href="mailto:info@example.com"><span>Write to us</span></a>'),
        );
    }

    public function testSubjectStaysReadable(): void
    {
        $this->assertSame('<a href="mailto:vasb@rknzcyr.pbz?subject=Order" data-obfuscated-email>Write to us</a>', $this->obfuscator->html('<a href="mailto:info@example.com?subject=Order">Write to us</a>'));
    }

    public function testSingleQuotedHrefKeepsItsQuotes(): void
    {
        $this->assertSame("<a href='mailto:vasb@rknzcyr.pbz' data-obfuscated-email>Write to us</a>", $this->obfuscator->html("<a href='mailto:info@example.com'>Write to us</a>"));
    }

    public function testRunningItTwiceChangesNothing(): void
    {
        $once = $this->obfuscator->html('<p>info@example.com</p>');

        $this->assertSame($once, $this->obfuscator->html($once));
    }

    /**
     * An address in an attribute is not a visible address: rewriting an image
     * title or a data attribute would break the markup.
     */
    public function testAddressInsideAnAttributeIsLeftAlone(): void
    {
        $html = '<img src="x.png" alt="info@example.com">';

        $this->assertSame($html, $this->obfuscator->html($html));
    }

    public function testScriptContentIsLeftAlone(): void
    {
        $html = '<script>var a = "info@example.com";</script>';

        $this->assertSame($html, $this->obfuscator->html($html));
    }

    public function testStyleContentIsLeftAlone(): void
    {
        $html = '<style>/* info@example.com */</style>';

        $this->assertSame($html, $this->obfuscator->html($html));
    }

    /**
     * An attribute may legally hold an angle bracket. A scanner that ends the tag
     * there reads the remaining attributes as text - and wrote the link into the
     * href, which took the page apart.
     */
    public function testAngleBracketInAnAttributeDoesNotBreakTheTag(): void
    {
        $this->assertSame(
            '<a title="A > B" href="mailto:vasb@rknzcyr.pbz" data-obfuscated-email>Write to us</a>',
            $this->obfuscator->html('<a title="A > B" href="mailto:info@example.com">Write to us</a>'),
        );
    }

    public function testAddressInAnAttributeBehindAnAngleBracketIsLeftAlone(): void
    {
        $html = '<img alt="more > less" src="x.png" title="info@example.com">';

        $this->assertSame($html, $this->obfuscator->html($html));
    }

    /**
     * A form field shows its content as characters. A link written into it would
     * stand there as markup for everyone to read.
     */
    public function testTextareaIsLeftAlone(): void
    {
        $html = '<textarea>info@example.com</textarea>';

        $this->assertSame($html, $this->obfuscator->html($html));
    }

    public function testTitleIsLeftAlone(): void
    {
        $html = '<title>Contact info@example.com</title>';

        $this->assertSame($html, $this->obfuscator->html($html));
    }

    public function testCommentIsLeftAlone(): void
    {
        $html = '<!-- if a > b then info@example.com --><p>ok</p>';

        $this->assertSame($html, $this->obfuscator->html($html));
    }

    /**
     * A link inside a link is nothing a browser keeps: it closes the outer one,
     * and the page loses the link it had. The address stays readable instead.
     */
    public function testAddressAsTheLabelOfAnotherLinkStaysAsItIs(): void
    {
        $html = '<a href="/contact">info@example.com</a>';

        $this->assertSame($html, $this->obfuscator->html($html));
    }

    public function testUnclosedLinkKeepsItsAddressInTheAttribute(): void
    {
        $html = '<a href="mailto:info@example.com"><span>x</span>';

        $this->assertSame($html, $this->obfuscator->html($html));
    }

    public function testUppercaseMarkupIsRecognised(): void
    {
        $this->assertSame(
            '<a href="mailto:Vasb@Rknzcyr.pbz" data-obfuscated-email>Vasb@Rknzcyr.pbz</a>',
            $this->obfuscator->html('<A HREF="MAILTO:Info@Example.com">Info@Example.com</A>'),
        );
    }

    public function testMarkupWithoutAddressIsUnchanged(): void
    {
        $html = '<p>Call us: <a href="tel:+4934112345">0341 12345</a></p>';

        $this->assertSame($html, $this->obfuscator->html($html));
    }

    public function testEmptyMarkupStaysEmpty(): void
    {
        $this->assertSame('', $this->obfuscator->html(null));
    }
}
