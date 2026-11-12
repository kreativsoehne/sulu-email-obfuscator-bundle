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

namespace KreativSoehne\SuluEmailObfuscatorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class SuluEmailObfuscatorExtension extends Extension
{
    /**
     * @param array<mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{enabled: bool, automatic: bool, link_plain_addresses: bool, script: string, excluded_routes: list<string>} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('sulu_email_obfuscator.enabled', $config['enabled']);
        $container->setParameter('sulu_email_obfuscator.link_plain_addresses', $config['link_plain_addresses']);
        $container->setParameter('sulu_email_obfuscator.script', $config['script']);
        $container->setParameter('sulu_email_obfuscator.excluded_routes', $config['excluded_routes']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        // The listener belongs to the website, never to the administration
        // interface: a backend form field has to show the address the editor
        // typed. Sulu runs both from one code base and tells them apart by
        // "sulu.context", so that parameter decides here.
        //
        // Sulu renders the preview in the admin context, which means the preview
        // shows the plain address while visitors get the shifted one. That is the
        // intended difference, not an oversight.
        //
        // In a plain Symfony application the parameter does not exist. There the
        // listener is registered, because there is only one context.
        $context = $container->hasParameter('sulu.context') ? $container->getParameter('sulu.context') : 'website';

        if ($config['automatic'] && 'website' === $context) {
            $loader->load('services_website.yaml');
        }
    }

    public function getAlias(): string
    {
        return 'sulu_email_obfuscator';
    }
}
