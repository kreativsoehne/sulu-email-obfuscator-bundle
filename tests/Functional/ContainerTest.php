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

namespace KreativSoehne\SuluEmailObfuscatorBundle\Tests\Functional;

use KreativSoehne\SuluEmailObfuscatorBundle\DependencyInjection\SuluEmailObfuscatorExtension;
use KreativSoehne\SuluEmailObfuscatorBundle\EventListener\ObfuscateResponseListener;
use KreativSoehne\SuluEmailObfuscatorBundle\Obfuscator\DecoderScript;
use KreativSoehne\SuluEmailObfuscatorBundle\Obfuscator\EmailObfuscator;
use KreativSoehne\SuluEmailObfuscatorBundle\Twig\EmailObfuscatorExtension;
use KreativSoehne\SuluEmailObfuscatorBundle\Twig\EmailObfuscatorRuntime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiles the container the way a real application would, so a broken service
 * definition fails here instead of in someone's project.
 */
final class ContainerTest extends TestCase
{
    public function testWebsiteContextGetsTheListener(): void
    {
        $container = $this->compile('website');

        $this->assertInstanceOf(EmailObfuscator::class, $container->get(EmailObfuscator::class));
        $this->assertInstanceOf(DecoderScript::class, $container->get(DecoderScript::class));
        $this->assertInstanceOf(EmailObfuscatorExtension::class, $container->get(EmailObfuscatorExtension::class));
        $this->assertInstanceOf(EmailObfuscatorRuntime::class, $container->get(EmailObfuscatorRuntime::class));
        $this->assertInstanceOf(ObfuscateResponseListener::class, $container->get(ObfuscateResponseListener::class));
    }

    /**
     * The administration interface has to show the address an editor typed, so
     * the listener does not exist there. The filters do - a backend template may
     * still want them.
     */
    public function testAdminContextHasNoListener(): void
    {
        $container = $this->compile('admin');

        $this->assertFalse($container->has(ObfuscateResponseListener::class));
        $this->assertInstanceOf(EmailObfuscatorRuntime::class, $container->get(EmailObfuscatorRuntime::class));
    }

    /**
     * Without Sulu there is only one context, and the bundle behaves like any
     * other Symfony bundle.
     */
    public function testPlainSymfonyGetsTheListener(): void
    {
        $container = $this->compile(null);

        $this->assertInstanceOf(ObfuscateResponseListener::class, $container->get(ObfuscateResponseListener::class));
    }

    public function testAutomaticCanBeSwitchedOff(): void
    {
        $container = $this->compile('website', ['automatic' => false]);

        $this->assertFalse($container->has(ObfuscateResponseListener::class));
    }

    public function testListenerIsRegisteredOnTheResponseEvent(): void
    {
        $container = $this->compile('website', compile: false);
        $tags = $container->getDefinition(ObfuscateResponseListener::class)->getTag('kernel.event_listener');

        $this->assertSame([['event' => 'kernel.response', 'priority' => -100]], $tags);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function compile(?string $context, array $config = [], bool $compile = true): ContainerBuilder
    {
        $container = new ContainerBuilder();

        if (null !== $context) {
            $container->setParameter('sulu.context', $context);
        }

        (new SuluEmailObfuscatorExtension())->load([$config], $container);

        foreach ($container->getDefinitions() as $definition) {
            $definition->setPublic(true);
        }

        if ($compile) {
            $container->compile();
        }

        return $container;
    }
}
