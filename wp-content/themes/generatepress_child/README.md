# GeneratePress Child Theme

## Custom ACF local JSON

The child theme loads ACF local JSON settings from:

- `acf/json/`

This behavior is registered in:

- `acf/local-json.php`

Current behavior:

- Saves ACF field group JSON into the child theme
- Loads ACF field group JSON from the child theme

This keeps field group definitions versionable alongside theme code.

## Custom taxonomy term shortcodes

The child theme loads custom shortcode logic from:

- `shortcodes/taxonomy-terms.php`

This file is required from:

- `functions.php`

### Available shortcodes

#### `gp_term_text`

Renders text from taxonomy terms attached to the current loop post.

Supported attributes:

- `taxonomy` - The taxonomy slug to read terms from.
- `count` - The maximum number of terms to render from the current post. "0" returns all terms.
- `source` - Where the value comes from: `term` for a `WP_Term` property or `acf` for an ACF term field.
- `field` - The term property or ACF field name to output.
- `separator` - The string placed between multiple rendered term values.
- `tag` - Optional wrapper HTML tag around the full shortcode output.
- `class` - Optional CSS class name(s) for the outer wrapper tag.
- `item_tag` - Optional wrapper HTML tag around each individual rendered term value.
- `item_class` - Optional CSS class name(s) for each individual item wrapper.

Examples:

```text
[gp_term_text taxonomy="instructor" count="1" source="term" field="name"]
```

```text
[gp_term_text taxonomy="instructor" count="3" source="term" field="slug" separator=", "]
```

#### `gp_term_image`

Renders image output from taxonomy terms attached to the current loop post.

Supported attributes:

- `taxonomy` - The taxonomy slug to read terms from.
- `count` - The maximum number of terms to render from the current post. "0" returns all terms.
- `source` - Where the image value comes from: `term` or `acf`.
- `field` - The term property or ACF field name that contains the image value.
- `size` - The WordPress image size to render, such as `thumbnail`.
- `separator` - The string placed between multiple rendered images.
- `tag` - Optional wrapper HTML tag around the full shortcode output.
- `class` - Optional CSS class name(s) for the outer wrapper tag.
- `item_tag` - Optional wrapper HTML tag around each individual rendered image.
- `item_class` - Optional CSS class name(s) for each individual item wrapper.
- `image_class` - Optional CSS class name(s) applied directly to the rendered `<img>` element.

Example:

```text
[gp_term_image taxonomy="instructor" count="1" source="acf" field="instructor_photo" size="thumbnail"]
```

## Notes

- These shortcodes are post-type agnostic and use the active loop post.
- `source="term"` reads values from the `WP_Term` object.
- `source="acf"` reads ACF term fields.
- If no matching terms or fields are found, the shortcode returns empty output.
