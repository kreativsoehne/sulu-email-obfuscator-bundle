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

use KreativSoehne\SuluEmailObfuscatorBundle\EventListener\ObfuscateResponseListener;
use KreativSoehne\SuluEmailObfuscatorBundle\Obfuscator\DecoderScript;
use KreativSoehne\SuluEmailObfuscatorBundle\Obfuscator\EmailObfuscator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ObfuscateResponseListenerTest extends TestCase
{
    public function testPageIsRewrittenAndGetsTheScript(): void
    {
        $response = $this->handle('<html><body><p>info@example.com</p></body></html>');
        $content = (string) $response->getContent();

        $this->assertStringContainsString('mailto:vasb@rknzcyr.pbz', $content);
        $this->assertStringNotContainsString('info@example.com', $content);
        $this->assertStringContainsString('<script>', $content);
        $this->assertStringEndsWith('</body></html>', $content);
    }

    /**
     * A page without an address carries no script either - it would have nothing
     * to do there.
     */
    public function testPageWithoutAddressStaysAsItIs(): void
    {
        $html = '<html><body><p>Call us.</p></body></html>';

        $this->assertSame($html, $this->handle($html)->getContent());
    }

    public function testScriptGoesToTheEndWhenThereIsNoBody(): void
    {
        $content = (string) $this->handle('<p>info@example.com</p>')->getContent();

        $this->assertStringEndsWith('</script>', $content);
    }

    public function testExcludedRouteIsLeftAlone(): void
    {
        $html = '<html><body><p>info@example.com</p></body></html>';

        $this->assertSame($html, $this->handle($html, route: 'sulu_preview.render')->getContent());
    }

    /**
     * Sulu renders the preview with a website kernel, so the listener does see it.
     * The request carries the attribute "preview", and that is what tells them
     * apart - the route is the one of the page being shown.
     */
    public function testPreviewIsLeftAlone(): void
    {
        $html = '<html><body><p>info@example.com</p></body></html>';
        $response = new Response($html, Response::HTTP_OK, ['Content-Type' => 'text/html']);

        $request = new Request();
        $request->attributes->set('preview', true);

        $this->listener()(new ResponseEvent(
            $this->kernel(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        ));

        $this->assertSame($html, $response->getContent());
    }

    public function testDisabledListenerIsLeftAlone(): void
    {
        $html = '<html><body><p>info@example.com</p></body></html>';

        $this->assertSame($html, $this->handle($html, enabled: false)->getContent());
    }

    public function testJsonIsLeftAlone(): void
    {
        $json = '{"email":"info@example.com"}';

        $this->assertSame($json, $this->handle($json, contentType: 'application/json')->getContent());
    }

    public function testStreamedResponseIsLeftAlone(): void
    {
        $response = new StreamedResponse(static function (): void {
            echo '<p>info@example.com</p>';
        });
        $response->headers->set('Content-Type', 'text/html');

        $this->listener()(new ResponseEvent(
            $this->kernel(),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        ));

        // Nothing to assert on the body of a streamed response: that it survived
        // the listener without an error is the statement.
        $this->assertFalse($response->getContent());
    }

    public function testSubRequestIsLeftAlone(): void
    {
        $html = '<html><body><p>info@example.com</p></body></html>';
        $response = new Response($html, Response::HTTP_OK, ['Content-Type' => 'text/html']);

        $this->listener()(new ResponseEvent(
            $this->kernel(),
            new Request(),
            HttpKernelInterface::SUB_REQUEST,
            $response,
        ));

        $this->assertSame($html, $response->getContent());
    }

    public function testContentLengthFollowsTheNewBody(): void
    {
        $response = $this->handle(
            '<html><body><p>info@example.com</p></body></html>',
            headers: ['Content-Length' => '49'],
        );

        $this->assertSame((string) \strlen((string) $response->getContent()), $response->headers->get('Content-Length'));
    }

    /**
     * @param array<string, string> $headers
     */
    private function handle(
        string $html,
        string $contentType = 'text/html',
        ?string $route = null,
        bool $enabled = true,
        array $headers = [],
    ): Response {
        $response = new Response($html, Response::HTTP_OK, ['Content-Type' => $contentType] + $headers);

        $request = new Request();
        if (null !== $route) {
            $request->attributes->set('_route', $route);
        }

        $this->listener($enabled)(new ResponseEvent(
            $this->kernel(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        ));

        return $response;
    }

    private function listener(bool $enabled = true): ObfuscateResponseListener
    {
        return new ObfuscateResponseListener(
            new EmailObfuscator(),
            new DecoderScript(),
            $enabled,
            ['sulu_preview', 'sulu_admin'],
        );
    }

    private function kernel(): HttpKernelInterface
    {
        return $this->createMock(HttpKernelInterface::class);
    }
}
