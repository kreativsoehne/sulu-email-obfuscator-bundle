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

namespace KreativSoehne\SuluEmailObfuscatorBundle\EventListener;

use KreativSoehne\SuluEmailObfuscatorBundle\Obfuscator\DecoderScript;
use KreativSoehne\SuluEmailObfuscatorBundle\Obfuscator\EmailObfuscator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Rewrites the finished page.
 *
 * This is the reason the bundle exists: a Sulu site puts addresses in places no
 * template of yours controls - rich text from the editor, snippets, article
 * content, legal texts pulled from an API. A filter in a template only ever
 * catches the places somebody remembered. The response catches all of them.
 *
 * It runs late (priority -100) so that it sees what other listeners produced, and
 * before the HTTP cache stores anything - a cached page therefore holds the
 * shifted addresses, not the plain ones.
 */
final readonly class ObfuscateResponseListener
{
    public function __construct(
        private EmailObfuscator $obfuscator,
        private DecoderScript $script,
        private bool $enabled = true,
        /** @var list<string> */
        private array $excludedRoutes = [],
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        if (!$this->handles($event->getRequest(), $response)) {
            return;
        }

        $content = $response->getContent();

        if (!\is_string($content) || '' === $content) {
            return;
        }

        $obfuscated = $this->obfuscator->html($content);

        // The script only goes in where there is something to turn back. A page
        // without an address stays exactly as long as it was.
        if ($this->script->isAutomatic() && \str_contains($obfuscated, EmailObfuscator::ATTRIBUTE)) {
            $obfuscated = $this->inject($obfuscated);
        }

        if ($obfuscated === $content) {
            return;
        }

        $response->setContent($obfuscated);

        if ($response->headers->has('Content-Length')) {
            $response->headers->set('Content-Length', (string) \strlen($obfuscated));
        }
    }

    private function handles(Request $request, Response $response): bool
    {
        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return false;
        }

        if (!\str_contains((string) $response->headers->get('Content-Type', ''), 'text/html')) {
            return false;
        }

        // The preview is not the website, even though Sulu renders it with a
        // website kernel - it is the editors looking at their own content, and they
        // have to see the address they typed. Sulu marks such a request with the
        // attribute "preview"; the route name does not help here, because the
        // preview runs under the route of the page it shows.
        //
        // It matters for a second reason: the preview replaces parts of the
        // document while editing, and markup put in that way does not run scripts.
        // Whatever the listener shifted there would stay unreadable.
        if ($request->attributes->getBoolean('preview')) {
            return false;
        }

        $route = $request->attributes->get('_route');

        if (!\is_string($route)) {
            return true;
        }

        foreach ($this->excludedRoutes as $excluded) {
            if (\str_starts_with($route, $excluded)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Before the closing body tag, so the script sees the whole document. Is there
     * none - a fragment, a hand written error page - it goes at the end, where it
     * still runs.
     */
    private function inject(string $html): string
    {
        $tag = $this->script->tag();

        if ('' === $tag) {
            return $html;
        }

        $position = \strripos($html, '</body>');

        if (false === $position) {
            return $html . $tag;
        }

        return \substr($html, 0, $position) . $tag . \substr($html, $position);
    }
}
