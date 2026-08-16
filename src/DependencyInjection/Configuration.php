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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_email_obfuscator');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('enabled')
                    ->defaultTrue()
                    ->info('Switches the bundle off entirely; addresses then go out as they are.')
                ->end()
                ->booleanNode('automatic')
                    ->defaultTrue()
                    ->info('Rewrites the finished page in the website context. Off means the Twig filters are the only way in.')
                ->end()
                ->booleanNode('link_plain_addresses')
                    ->defaultTrue()
                    ->info('An address written as plain text becomes a link. Off leaves it readable, because only a link can be obfuscated.')
                ->end()
                ->enumNode('script')
                    ->values(['auto', 'manual', 'none'])
                    ->defaultValue('auto')
                    ->info('auto: injects the browser script before </body>. manual: the template calls sulu_email_obfuscator_script(). none: the project ships the script itself.')
                ->end()
                ->arrayNode('excluded_routes')
                    ->scalarPrototype()->end()
                    ->defaultValue(['sulu_preview', 'sulu_admin'])
                    ->info('Route names that stay untouched. A name matches by prefix.')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
