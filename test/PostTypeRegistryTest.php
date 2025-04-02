<?php

/*
 * Copyright (c) 2025 Martin Pettersson
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace N7e\WordPress;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WP_Post;

#[CoversClass(PostTypeRegistry::class)]
final class PostTypeRegistryTest extends TestCase
{
    use PHPMock;

    private PostTypeRegistry $registry;

    private MockObject $postTypeMock;
    private MockObject $metaBoxMock;
    private MockObject $taxonomyMock;
    private MockObject $postMock;

    #[Before]
    public function setUp(): void
    {
        $this->registry = new PostTypeRegistry();
        $this->postTypeMock = $this->getMockBuilder(PostType::class)->getMock();
        $this->metaBoxMock = $this->getMockBuilder(MetaBox::class)->getMock();
        $this->taxonomyMock = $this->getMockBuilder(Taxonomy::class)->getMock();
        $this->postMock = $this->getMockBuilder(WP_Post::class)->disableOriginalConstructor()->getMock();

        $this->postTypeMock->method('key')->willReturn('post-type-key');
        $this->metaBoxMock->method('id')->willReturn('meta-box-id');
        $this->taxonomyMock->method('key')->willReturn('taxonomy-type-key');
    }

    #[Test]
    public function shouldRegisterPostTypeAtAppropriateHook(): void
    {
        $this->getFunctionMock(__NAMESPACE__, 'add_action')
            ->expects($this->once())
            ->with('init', $this->isCallable());
        $this->getFunctionMock(__NAMESPACE__, 'register_post_type')
            ->expects($this->never());
        $this->postTypeMock->expects($this->never())->method($this->anything());

        $this->registry->register($this->postTypeMock);
    }

    #[Test]
    public function shouldRegisterPostType(): void
    {
        $this->getFunctionMock(__NAMESPACE__, 'add_action')
            ->expects($this->once())
            ->with('init', $this->isCallable())
            ->willReturnCallback(static fn($hook, $callback) => $callback());
        $this->getFunctionMock(__NAMESPACE__, 'register_post_type')
            ->expects($this->once())
            ->with(
                $this->postTypeMock->key(),
                $this->callback(function ($array) {
                    $this->assertEquals($array['description'], $this->postTypeMock->description());
                    $this->assertEquals($array['labels'], $this->postTypeMock->labels());
                    $this->assertEquals($array['public'], $this->postTypeMock->isPublic());
                    $this->assertEquals($array['hierarchical'], $this->postTypeMock->isHierarchical());
                    $this->assertEquals($array['exclude_from_search'], ! $this->postTypeMock->isIncludedInSearch());
                    $this->assertEquals($array['publicly_queryable'], $this->postTypeMock->isPubliclyQueryable());
                    $this->assertEquals($array['show_ui'], $this->postTypeMock->hasUi());
                    $this->assertEquals($array['show_in_nav_menus'], $this->postTypeMock->isVisibleInNavigationMenus());
                    $this->assertEquals($array['show_in_admin_bar'], $this->postTypeMock->isVisibleInAdminBar());
                    $this->assertEquals($array['show_in_rest'], $this->postTypeMock->isIncludedInRestApi());
                    $this->assertEquals(
                        $array['map_meta_cap'],
                        $this->postTypeMock->isUsingDefaultMetaCapabilityHandling()
                    );
                    $this->assertEquals($array['can_export'], $this->postTypeMock->canBeExported());
                    $this->assertEquals($array['delete_with_user'], $this->postTypeMock->isDeletedWithUser());
                    $this->assertEquals($array['has_archive'], $this->postTypeMock->archive());
                    $this->assertEquals($array['show_in_menu'], $this->postTypeMock->menuLocation());
                    $this->assertEquals($array['rest_base'], $this->postTypeMock->restApiBase());
                    $this->assertEquals($array['rest_controller_class'], $this->postTypeMock->restApiControllerClass());
                    $this->assertEquals($array['menu_position'], $this->postTypeMock->menuPosition());
                    $this->assertEquals($array['menu_icon'], $this->postTypeMock->menuIcon());
                    $this->assertEquals($array['capability_type'], $this->postTypeMock->capabilityBase());
                    $this->assertEquals($array['capabilities'], $this->postTypeMock->capabilities());
                    $this->assertEquals($array['supports'], $this->postTypeMock->features());
                    $this->assertEmpty($array['taxonomies']);
                    $this->assertEquals($array['rewrite'], $this->postTypeMock->rewriteRules());
                    $this->assertEquals($array['query_var'], $this->postTypeMock->queryParameterKey());
                    $this->assertEquals($array['template'], $this->postTypeMock->templateBlocks());
                    $this->assertEquals($array['template_lock'], $this->postTypeMock->templateLockStrategy());
                    $this->assertIsCallable($array['register_meta_box_cb']);

                    return true;
                })
            );
        $this->getFunctionMock(__NAMESPACE__, 'remove_post_type_support');

        $this->registry->register($this->postTypeMock);
    }

    #[Test]
    public function shouldRemoveUnusedFeatures(): void
    {
        $invokations = $this->exactly(2);

        $this->getFunctionMock(__NAMESPACE__, 'add_action')
            ->expects($this->once())
            ->with('init', $this->isCallable())
            ->willReturnCallback(static fn($hook, $callback) => $callback());
        $this->getFunctionMock(__NAMESPACE__, 'register_post_type');
        $this->getFunctionMock(__NAMESPACE__, 'remove_post_type_support')
            ->expects($invokations)
            ->willReturnCallback(function ($key, $feature) use ($invokations) {
                $this->assertEquals($this->postTypeMock->key(), $key);

                switch ($invokations->numberOfInvocations()) {
                    case 1:
                        $this->assertEquals('page-attributes', $feature);
                        break;
                    case 2:
                        $this->assertEquals('post-formats', $feature);
                        break;
                }
            });

        $this->postTypeMock->method('features')
            ->willReturn([
                'title',
                'editor',
                'excerpt',
                'author',
                'trackbacks',
                'thumbnail',
                'custom-fields',
                'comments',
                'revisions'
            ]);

        $this->registry->register($this->postTypeMock);
    }

    #[Test]
    public function shouldRegisterMetaBoxes(): void
    {
        $this->getFunctionMock(__NAMESPACE__, 'add_action')
            ->expects($this->once())
            ->with('init', $this->isCallable())
            ->willReturnCallback(static fn($hook, $callback) => $callback());
        $this->getFunctionMock(__NAMESPACE__, 'register_post_type')
            ->expects($this->once())
            ->willReturnCallback(fn($key, $array) => $array['register_meta_box_cb']($this->postMock));
        $this->getFunctionMock(__NAMESPACE__, 'remove_post_type_support');
        $this->getFunctionMock(__NAMESPACE__, 'add_meta_box')
            ->expects($this->once())
            ->with(
                $this->metaBoxMock->id(),
                $this->metaBoxMock->title(),
                $this->isCallable(),
                null,
                $this->metaBoxMock->context(),
                $this->metaBoxMock->priority()
            )
            ->willReturnCallback(fn($id, $title, $renderCallback) => $renderCallback());
        $this->metaBoxMock->expects($this->once())->method('render')->with($this->postMock);

        $this->postTypeMock->metaBoxes->add($this->metaBoxMock);
        $this->registry->register($this->postTypeMock);
    }

    #[Test]
    public function shouldRegisterTaxonomies(): void
    {
        $this->getFunctionMock(__NAMESPACE__, 'add_action')
            ->expects($this->once())
            ->with('init', $this->isCallable())
            ->willReturnCallback(static fn($hook, $callback) => $callback());
        $this->getFunctionMock(__NAMESPACE__, 'register_post_type');
        $this->getFunctionMock(__NAMESPACE__, 'remove_post_type_support');
        $this->getFunctionMock(__NAMESPACE__, 'register_taxonomy')
            ->expects($this->once())
            ->with(
                $this->taxonomyMock->key(),
                $this->postTypeMock->key(),
                $this->callback(function ($array) {
                    $this->assertEquals($array['description'], $this->taxonomyMock->description());
                    $this->assertEquals($array['labels'], $this->taxonomyMock->labels());
                    $this->assertEquals($array['public'], $this->taxonomyMock->isPublic());
                    $this->assertEquals($array['publicly_queryable'], $this->taxonomyMock->isPubliclyQueryable());
                    $this->assertEquals($array['hierarchical'], $this->taxonomyMock->isHierarchical());
                    $this->assertEquals($array['show_ui'], $this->taxonomyMock->hasUi());
                    $this->assertEquals($array['show_in_menu'], $this->taxonomyMock->isVisibleInMenu());
                    $this->assertEquals($array['show_in_nav_menus'], $this->taxonomyMock->isVisibleInNavigationMenus());
                    $this->assertEquals($array['show_in_rest'], $this->taxonomyMock->isIncludedInRestApi());
                    $this->assertEquals($array['rest_base'], $this->taxonomyMock->restApiBase());
                    $this->assertEquals($array['rest_namespace'], $this->taxonomyMock->restApiNamespace());
                    $this->assertEquals($array['rest_controller_class'], $this->taxonomyMock->restApiControllerClass());
                    $this->assertEquals(
                        $array['show_tag_cloud'],
                        $this->taxonomyMock->isVisibleInTagCloudWidgetControls()
                    );
                    $this->assertEquals($array['show_in_quick_edit'], $this->taxonomyMock->isVisibleInQuickEdit());
                    $this->assertEquals($array['show_admin_column'], $this->taxonomyMock->haveAdminColumn());
                    $this->assertEquals($array['capabilities'], $this->taxonomyMock->capabilities());
                    $this->assertEquals($array['rewrite'], $this->taxonomyMock->rewriteRules());
                    $this->assertEquals($array['query_var'], $this->taxonomyMock->queryParameterKey());
                    $this->assertEquals($array['default_term'], $this->taxonomyMock->defaultTerm());
                    $this->assertEquals($array['sort'], $this->taxonomyMock->isSorted());

                    return true;
                })
            );

        $this->postTypeMock->taxonomies->add($this->taxonomyMock);
        $this->registry->register($this->postTypeMock);
    }
}
