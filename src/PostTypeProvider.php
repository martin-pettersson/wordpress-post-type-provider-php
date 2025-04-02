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
use N7e\WordPress\PostType\PostType;
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
     * Configuration object.
     *
     * @var \N7e\Configuration\ConfigurationInterface
     */
    private readonly ConfigurationInterface $configuration;

    /**
     * Dependency injection container.
     *
     * @var \N7e\DependencyInjection\ContainerInterface
     */
    private readonly ContainerInterface $container;

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
        $this->configuration = $container->get(ConfigurationInterface::class);
        $this->container = $container;

        foreach ($this->configuration->get('postTypes', []) as $postType) {
            $this->register($postType);
        }
    }

    /**
     * Register given post type definition.
     *
     * @param array $postTypeDefinition Arbitrary post type definition.
     * @throws \N7e\WordPress\InvalidPostTypeDefinitionException If any post type definition is invalid.
     * @throws \Psr\Container\ContainerExceptionInterface If unable to construct any classes.
     */
    private function register(array $postTypeDefinition): void
    {
        if (! array_key_exists('postType', $postTypeDefinition)) {
            throw new InvalidPostTypeDefinitionException();
        }

        /** @var PostType $postType */
        $postType = $this->container->construct($postTypeDefinition['postType']);

        foreach ($postTypeDefinition['taxonomies'] ?? [] as $taxonomy) {
            $postType->taxonomies->register($this->container->construct($taxonomy));
        }

        foreach ($postTypeDefinition['metaBoxes'] ?? [] as $metaBox) {
            $postType->metaBoxes->register($this->container->construct($metaBox));
        }

        $this->postTypes->register($postType);
    }
}
