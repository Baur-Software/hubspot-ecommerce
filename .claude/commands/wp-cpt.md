# WordPress Custom Post Type Generator

You need to generate a WordPress Custom Post Type (CPT) registration.

## Steps:

1. Ask the user for:
   - Post type slug (lowercase, no spaces, max 20 chars)
   - Singular name (e.g., "Product")
   - Plural name (e.g., "Products")
   - Description
   - Features to support (title, editor, thumbnail, excerpt, etc.)
   - Whether it should be public
   - Whether it should have an archive page
   - Whether to show in REST API (for Gutenberg support)
   - Custom taxonomies (if any)

2. Generate a properly structured function that:
   - Registers the CPT on the 'init' hook
   - Includes all necessary labels for the admin UI
   - Uses proper text domain for internationalization
   - Follows WordPress naming conventions
   - Includes complete PHPDoc comments
   - Sets appropriate capabilities
   - Configures rewrite rules properly

3. If custom taxonomies are needed, generate those as well

4. Remind the user to:
   - Flush rewrite rules after activation (Settings > Permalinks)
   - Add the text domain to their translation files
   - Test the CPT in both admin and frontend

## Example Output:

```php
/**
 * Register Custom Post Type.
 *
 * @since 1.0.0
 */
function my_plugin_register_cpt() {
    $labels = array(
        'name'                  => _x( 'Products', 'Post Type General Name', 'my-plugin' ),
        'singular_name'         => _x( 'Product', 'Post Type Singular Name', 'my-plugin' ),
        'menu_name'             => __( 'Products', 'my-plugin' ),
        'name_admin_bar'        => __( 'Product', 'my-plugin' ),
        'archives'              => __( 'Product Archives', 'my-plugin' ),
        'attributes'            => __( 'Product Attributes', 'my-plugin' ),
        'parent_item_colon'     => __( 'Parent Product:', 'my-plugin' ),
        'all_items'             => __( 'All Products', 'my-plugin' ),
        'add_new_item'          => __( 'Add New Product', 'my-plugin' ),
        'add_new'               => __( 'Add New', 'my-plugin' ),
        'new_item'              => __( 'New Product', 'my-plugin' ),
        'edit_item'             => __( 'Edit Product', 'my-plugin' ),
        'update_item'           => __( 'Update Product', 'my-plugin' ),
        'view_item'             => __( 'View Product', 'my-plugin' ),
        'view_items'            => __( 'View Products', 'my-plugin' ),
        'search_items'          => __( 'Search Product', 'my-plugin' ),
        'not_found'             => __( 'Not found', 'my-plugin' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'my-plugin' ),
    );

    $args = array(
        'label'                 => __( 'Product', 'my-plugin' ),
        'description'           => __( 'Product Description', 'my-plugin' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-products',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => true, // Gutenberg support
        'rewrite'               => array( 'slug' => 'products' ),
    );

    register_post_type( 'product', $args );
}
add_action( 'init', 'my_plugin_register_cpt', 0 );
```

Remember to flush permalinks after adding this code!
