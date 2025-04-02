<?php

/*
 * Copyright (c) 2025 Martin Pettersson
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace N7e\WordPress;

use WP_Post;

/**
 * Represents a WordPress post type registry.
 */
final class PostTypeRegistry
{
    /**
     * Available post type features.
     *
     * @var string[]
     */
    private const array AVAILABLE_FEATURES = [
        'title',
        'editor',
        'excerpt',
        'author',
        'trackbacks',
        'thumbnail',
        'custom-fields',
        'comments',
        'revisions',
        'page-attributes',
        'post-formats'
    ];

    /**
     * Register given post type.
     *
     * @param \N7e\WordPress\PostType $postType Arbitrary post type.
     */
    public function register(PostType $postType): void
    {
        add_action('init', fn() => $this->registerPostType($postType));
    }

    /**
     * Register given post type.
     *
     * @param \N7e\WordPress\PostType $postType Arbitrary post type.
     */
    private function registerPostType(PostType $postType): void
    {
        /** @var \N7e\WordPress\Taxonomy[] $taxonomies */
        $taxonomies = iterator_to_array($postType->taxonomies);

        register_post_type(
            $postType->key(),
            [
                'description' => $postType->description(),
                'labels' => $postType->labels(),
                'public' => $postType->isPublic(),
                'hierarchical' => $postType->isHierarchical(),
                'exclude_from_search' => ! $postType->isIncludedInSearch(),
                'publicly_queryable' => $postType->isPubliclyQueryable(),
                'show_ui' => $postType->hasUi(),
                'show_in_nav_menus' => $postType->isVisibleInNavigationMenus(),
                'show_in_admin_bar' => $postType->isVisibleInAdminBar(),
                'show_in_rest' => $postType->isIncludedInRestApi(),
                'map_meta_cap' => $postType->isUsingDefaultMetaCapabilityHandling(),
                'can_export' => $postType->canBeExported(),
                'delete_with_user' => $postType->isDeletedWithUser(),
                'has_archive' => $postType->archive(),
                'show_in_menu' => $postType->menuLocation(),
                'rest_base' => $postType->restApiBase(),
                'rest_controller_class' => $postType->restApiControllerClass(),
                'menu_position' => $postType->menuPosition(),
                'menu_icon' => $postType->menuIcon(),
                'capability_type' => $postType->capabilityBase(),
                'capabilities' => $postType->capabilities(),
                'supports' => $postType->features(),
                'taxonomies' => array_map(static fn($taxonomy) => $taxonomy->key(), iterator_to_array($taxonomies)),
                'rewrite' => $postType->rewriteRules(),
                'query_var' => $postType->queryParameterKey(),
                'template' => $postType->templateBlocks(),
                'template_lock' => $postType->templateLockStrategy(),
                'register_meta_box_cb' => fn($post) => $this->registerMetaBoxes($postType, $post)
            ]
        );

        foreach (array_diff(PostTypeRegistry::AVAILABLE_FEATURES, $postType->features()) as $feature) {
            remove_post_type_support($postType->key(), $feature);
        }

        foreach ($taxonomies as $taxonomy) {
            $this->registerTaxonomy($taxonomy, $postType);
        }
    }

    /**
     * Register registered meta boxes of given post type.
     *
     * @param \N7e\WordPress\PostType $postType Arbitrary post type.
     * @param \WP_Post $post Post object passed to meta box render callback.
     */
    private function registerMetaBoxes(PostType $postType, WP_Post $post): void
    {
        /** @var \N7e\WordPress\MetaBox $metaBox */
        foreach ($postType->metaBoxes as $metaBox) {
            add_meta_box(
                $metaBox->id(),
                $metaBox->title(),
                static fn() => $metaBox->render($post),
                null,
                $metaBox->context(),
                $metaBox->priority()
            );
        }
    }

    /**
     * Register given taxonomy for the associated post type.
     *
     * @param \N7e\WordPress\Taxonomy $taxonomy Arbitrary taxonomy.
     * @param \N7e\WordPress\PostType $postType Associated post type.
     */
    private function registerTaxonomy(Taxonomy $taxonomy, PostType $postType): void
    {
        register_taxonomy(
            $taxonomy->key(),
            $postType->key(),
            [
                'description' => $taxonomy->description(),
                'labels' => $taxonomy->labels(),
                'public' => $taxonomy->isPublic(),
                'publicly_queryable' => $taxonomy->isPubliclyQueryable(),
                'hierarchical' => $taxonomy->isHierarchical(),
                'show_ui' => $taxonomy->hasUi(),
                'show_in_menu' => $taxonomy->isVisibleInMenu(),
                'show_in_nav_menus' => $taxonomy->isVisibleInNavigationMenus(),
                'show_in_rest' => $taxonomy->isIncludedInRestApi(),
                'rest_base' => $taxonomy->restApiBase(),
                'rest_namespace' => $taxonomy->restApiNamespace(),
                'rest_controller_class' => $taxonomy->restApiControllerClass(),
                'show_tag_cloud' => $taxonomy->isVisibleInTagCloudWidgetControls(),
                'show_in_quick_edit' => $taxonomy->isVisibleInQuickEdit(),
                'show_admin_column' => $taxonomy->haveAdminColumn(),
                'capabilities' => $taxonomy->capabilities(),
                'rewrite' => $taxonomy->rewriteRules(),
                'query_var' => $taxonomy->queryParameterKey(),
                'default_term' => $taxonomy->defaultTerm(),
                'sort' => $taxonomy->isSorted()
            ]
        );
    }
}
