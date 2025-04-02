<?php

/*
 * Copyright (c) 2025 Martin Pettersson
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace N7e\WordPress;

use RuntimeException;

/**
 * Represents an exception thrown when a post type definition is invalid.
 */
final class InvalidPostTypeDefinitionException extends RuntimeException implements PostTypeProviderExceptionInterface
{
    /**
     * Create a new exception instance.
     */
    public function __construct()
    {
        parent::__construct(
            'Post type definitions must contain properties for at least: postType'
        );
    }
}
