<?php

/*
 * Copyright (c) 2025 Martin Pettersson
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace N7e\WordPress;

use N7e\Configuration\ConfigurationInterface;
use N7e\DependencyInjection\ContainerBuilderInterface;
use N7e\DependencyInjection\ContainerInterface;
use N7e\ServiceProviderInterface;
use N7e\WordPress\PostType\PostTypeRegistry;
use Override;

/**
 * Provides WordPress post types.
 */
class PostTypeProvider implements ServiceProviderInterface
{
    /**
     * Registered post types.
     *
     * @var \N7e\WordPress\PostType\PostTypeRegistry
     */
    private readonly PostTypeRegistry $postTypes;

    /**
     * Create a new service provider instance.
     */
    public function __construct()
    {
        $this->postTypes = new PostTypeRegistry();
    }

    #[Override]
    public function configure(ContainerBuilderInterface $containerBuilder): void
    {
        $containerBuilder->addFactory(PostTypeRegistry::class, fn() => $this->postTypes)->singleton();
    }

    #[Override]
    public function load(ContainerInterface $container): void
    {
        /** @var \N7e\Configuration\ConfigurationInterface $configuration */
        $configuration = $container->get(ConfigurationInterface::class);

        foreach ($configuration->get('postTypes', []) as $postTypeClass) {
            $this->postTypes->register($container->construct($postTypeClass));
        }
    }
}
