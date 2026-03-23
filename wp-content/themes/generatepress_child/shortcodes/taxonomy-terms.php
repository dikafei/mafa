<?php

function mafa_get_loop_post_id() {
	$post_id = get_the_ID();

	if ( $post_id ) {
		return (int) $post_id;
	}

	global $post;

	if ( $post instanceof WP_Post ) {
		return (int) $post->ID;
	}

	return 0;
}

function mafa_get_shortcode_terms( $atts ) {
	$taxonomy = isset( $atts['taxonomy'] ) ? sanitize_key( $atts['taxonomy'] ) : '';

	if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}

	$post_id = mafa_get_loop_post_id();

	if ( ! $post_id ) {
		return array();
	}

	$terms = get_the_terms( $post_id, $taxonomy );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return array();
	}

	$terms = array_values( array_filter( $terms, function( $term ) {
		return $term instanceof WP_Term;
	} ) );

	if ( empty( $terms ) ) {
		return array();
	}

	$count = isset( $atts['count'] ) ? (int) $atts['count'] : 1;

	if ( $count > 0 ) {
		$terms = array_slice( $terms, 0, $count );
	}

	return $terms;
}

function mafa_get_term_field_value( WP_Term $term, $source, $field ) {
	if ( ! $field ) {
		return null;
	}

	if ( 'term' === $source ) {
		if ( isset( $term->{$field} ) ) {
			return $term->{$field};
		}

		return null;
	}

	if ( 'acf' === $source && function_exists( 'get_field' ) ) {
		return get_field( $field, $term );
	}

	return null;
}

function mafa_normalize_class_names( $classes ) {
	$classes = is_string( $classes ) ? trim( $classes ) : '';

	if ( '' === $classes ) {
		return '';
	}

	$sanitized = array();

	foreach ( preg_split( '/\s+/', $classes ) as $class_name ) {
		$class_name = sanitize_html_class( $class_name );

		if ( '' !== $class_name ) {
			$sanitized[] = $class_name;
		}
	}

	return implode( ' ', array_unique( $sanitized ) );
}

function mafa_wrap_shortcode_content( $content, $tag = '', $class = '' ) {
	$content = (string) $content;

	if ( '' === $content ) {
		return '';
	}

	$tag = is_string( $tag ) ? trim( $tag ) : '';

	if ( '' === $tag ) {
		return $content;
	}

	$class = mafa_normalize_class_names( $class );
	$attributes = '';

	if ( '' !== $class ) {
		$attributes = ' class="' . esc_attr( $class ) . '"';
	}

	return '<' . tag_escape( $tag ) . $attributes . '>' . $content . '</' . tag_escape( $tag ) . '>';
}

function mafa_collect_text_fragments( $value ) {
	if ( is_array( $value ) ) {
		$fragments = array();

		foreach ( $value as $item ) {
			$fragments = array_merge( $fragments, mafa_collect_text_fragments( $item ) );
		}

		return $fragments;
	}

	if ( is_scalar( $value ) ) {
		$text = trim( (string) $value );

		if ( '' !== $text ) {
			return array( $text );
		}
	}

	return array();
}

function mafa_render_term_text_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'taxonomy'   => '',
			'count'      => 1,
			'source'     => 'term',
			'field'      => 'name',
			'separator'  => ', ',
			'tag'        => '',
			'class'      => '',
			'item_tag'   => '',
			'item_class' => '',
		),
		$atts,
		'gp_term_text'
	);

	$atts['source'] = in_array( $atts['source'], array( 'term', 'acf' ), true ) ? $atts['source'] : 'term';
	$atts['field'] = is_string( $atts['field'] ) ? trim( $atts['field'] ) : '';

	if ( '' === $atts['field'] ) {
		return '';
	}

	$terms = mafa_get_shortcode_terms( $atts );

	if ( empty( $terms ) ) {
		return '';
	}

	$output = array();

	foreach ( $terms as $term ) {
		$value = mafa_get_term_field_value( $term, $atts['source'], $atts['field'] );
		$fragments = mafa_collect_text_fragments( $value );

		if ( empty( $fragments ) ) {
			continue;
		}

		$text = esc_html( implode( ', ', $fragments ) );
		$output[] = mafa_wrap_shortcode_content( $text, $atts['item_tag'], $atts['item_class'] );
	}

	if ( empty( $output ) ) {
		return '';
	}

	return mafa_wrap_shortcode_content( implode( $atts['separator'], $output ), $atts['tag'], $atts['class'] );
}

function mafa_get_image_html( $value, $size, $image_class ) {
	$image_class = mafa_normalize_class_names( $image_class );
	$image_attributes = array();

	if ( '' !== $image_class ) {
		$image_attributes['class'] = $image_class;
	}

	if ( is_numeric( $value ) ) {
		return wp_get_attachment_image( (int) $value, $size, false, $image_attributes );
	}

	if ( is_array( $value ) ) {
		$image_id = 0;

		if ( ! empty( $value['ID'] ) ) {
			$image_id = (int) $value['ID'];
		} elseif ( ! empty( $value['id'] ) ) {
			$image_id = (int) $value['id'];
		}

		if ( $image_id ) {
			return wp_get_attachment_image( $image_id, $size, false, $image_attributes );
		}

		$image_url = '';

		if ( ! empty( $value['sizes'][ $size ] ) ) {
			$image_url = $value['sizes'][ $size ];
		} elseif ( ! empty( $value['url'] ) ) {
			$image_url = $value['url'];
		}

		if ( '' === $image_url ) {
			return '';
		}

		$attributes = '';

		if ( '' !== $image_class ) {
			$attributes .= ' class="' . esc_attr( $image_class ) . '"';
		}

		$alt = '';
		if ( ! empty( $value['alt'] ) ) {
			$alt = (string) $value['alt'];
		}

		return '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $alt ) . '"' . $attributes . ' />';
	}

	if ( is_string( $value ) && '' !== trim( $value ) ) {
		$attributes = '';

		if ( '' !== $image_class ) {
			$attributes .= ' class="' . esc_attr( $image_class ) . '"';
		}

		return '<img src="' . esc_url( trim( $value ) ) . '" alt=""' . $attributes . ' />';
	}

	return '';
}

function mafa_render_term_image_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'taxonomy'    => '',
			'count'       => 1,
			'source'      => 'acf',
			'field'       => '',
			'size'        => 'thumbnail',
			'separator'   => '',
			'tag'         => '',
			'class'       => '',
			'item_tag'    => '',
			'item_class'  => '',
			'image_class' => '',
		),
		$atts,
		'gp_term_image'
	);

	$atts['source'] = in_array( $atts['source'], array( 'term', 'acf' ), true ) ? $atts['source'] : 'acf';
	$atts['field'] = is_string( $atts['field'] ) ? trim( $atts['field'] ) : '';
	$atts['size'] = is_string( $atts['size'] ) && '' !== trim( $atts['size'] ) ? trim( $atts['size'] ) : 'thumbnail';

	if ( '' === $atts['field'] ) {
		return '';
	}

	$terms = mafa_get_shortcode_terms( $atts );

	if ( empty( $terms ) ) {
		return '';
	}

	$output = array();

	foreach ( $terms as $term ) {
		$value = mafa_get_term_field_value( $term, $atts['source'], $atts['field'] );
		$image = mafa_get_image_html( $value, $atts['size'], $atts['image_class'] );

		if ( '' === $image ) {
			continue;
		}

		$output[] = mafa_wrap_shortcode_content( $image, $atts['item_tag'], $atts['item_class'] );
	}

	if ( empty( $output ) ) {
		return '';
	}

	return mafa_wrap_shortcode_content( implode( $atts['separator'], $output ), $atts['tag'], $atts['class'] );
}

add_shortcode( 'gp_term_text', 'mafa_render_term_text_shortcode' );
add_shortcode( 'gp_term_image', 'mafa_render_term_image_shortcode' );



function get_term_slug_list( $atts ) {

    $atts = shortcode_atts([
        'tax' => '',
    ], $atts);

    if ( empty($atts['tax']) ) {
        return '';
    }

    global $post;

    if ( ! $post ) {
        return '';
    }
    $terms = get_the_terms( $post->ID, $atts['tax'] );

    if ( empty($terms) || is_wp_error($terms) ) {
        return '';
    }

    return implode(', ', wp_list_pluck($terms, 'slug'));
}

add_shortcode('term_slugs', 'get_term_slug_list');




// GUILDS

// 1. Set main query without pagination
add_action('pre_get_posts', function($query) {
    if (!is_admin() && $query->is_main_query() && is_post_type_archive('guilds')) {
        $query->set('posts_per_page', -1);
        $query->set('nopaging', true);
    }
});

// 2. Disable default loop GP
add_action('wp', function() {
    if (is_post_type_archive('guilds')) {
        remove_all_actions('generate_do_archive_loop');
    }
});

add_shortcode('guilds_by_states', function() {
	static $rendered = false;
    if ($rendered) return '';
    $rendered = true;
	
    ob_start();

    $states = get_terms([
        'taxonomy'   => 'state',
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);

    if (empty($states) || is_wp_error($states)) {
        echo '<p>Tidak ada guild yang ditemukan.</p>';
        return ob_get_clean();
    }

    foreach ($states as $state) :

        $query = new WP_Query([
            'post_type'      => 'guilds',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
            'tax_query'      => [[
                'taxonomy' => 'state',
                'field'    => 'slug',
                'terms'    => $state->slug,
            ]],
        ]);

        if (!$query->have_posts()) continue;

        ?>
        <section class="state-group" id="state-<?php echo esc_attr($state->slug); ?>">

            <div class="state-group__header">
                <h2 class="state-group__title"><?php echo esc_html($state->name); ?></h2>
                <span class="state-group__count"><?php echo $state->count; ?> guild</span>
            </div>

            <div class="state-group__grid">
                <?php while ($query->have_posts()) : $query->the_post();

                    $website         = get_field('website');
                    $email           = get_field('email');
                    $date_time       = get_field('date_time');
                    $time            = get_field('time');
                    $location        = get_field('location');
                    $hybrid_meetings = get_field('hybrid_meetings');
                    $mafa_rep        = get_field('mafa_rep');
                    $rep_email       = get_field('rep_email');
                    $more_info       = get_field('more_info');
                    $facebook        = get_field('facebook');
                    $ravelry         = get_field('ravelry');
                    $twitter         = get_field('twitter');
                    $pinterest       = get_field('pinterest');
                    $instagram       = get_field('instagram');
                    $other_social    = get_field('other_social_media');

                ?>

                    <article class="guild-card" id="guild-<?php the_ID(); ?>">

                        <div class="guild-card__header">
                            <h3 class="guild-card__title"><?php the_title(); ?></h3>
                        </div>

                        <div class="guild-card__body">
                            <ul class="guild-card__meta-list">

                                <?php if ($website) : ?>
                                    <li>
                                        <span class="meta-label">🌐 Guild Website</span>
                                        <span class="meta-value">
                                            <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener">
                                                <?php echo esc_html($website); ?>
                                            </a>
                                        </span>
                                    </li>
                                <?php endif; ?>

                                <?php if ($email) : ?>
                                    <li>
                                        <span class="meta-label">✉️ Guild Email Address</span>
                                        <span class="meta-value"><a href="mailto:<?php echo esc_attr($email); ?>">
                                                <?php echo esc_html($email); ?>
                                            </a>
                                        </span>
                                    </li>
                                <?php endif; ?>

                                <?php if ($date_time) : ?>
                                    <li>
                                        <span class="meta-label">📅 Meeting Date/Time</span>
                                        <span class="meta-value"><?php echo esc_html($date_time); ?></span>
                                    </li>
                                <?php endif; ?>

                                <?php if ($time) : ?>
                                    <li>
                                        <span class="meta-label">🕐 Meeting Time</span>
                                        <span class="meta-value"><?php echo esc_html($time); ?></span>
                                    </li>
                                <?php endif; ?>

                                <?php if ($location) : ?>
                                    <li>
                                        <span class="meta-label">📍 Meeting Location</span>
                                        <span class="meta-value"><?php echo esc_html($location); ?></span>
                                    </li>
                                <?php endif; ?>

                                <?php if ($hybrid_meetings) : ?>
                                    <li>
                                        <span class="meta-label">💻 Hybrid Meetings?</span>
                                        <span class="meta-value"><?php echo esc_html($hybrid_meetings); ?></span>
                                    </li>
                                <?php endif; ?>

                                <?php if ($mafa_rep) : ?>
                                    <li>
                                        <span class="meta-label">👤 MAFA Representative</span>
                                        <span class="meta-value"><?php echo esc_html($mafa_rep); ?></span>
                                    </li>
                                <?php endif; ?>

                                <?php if ($rep_email) : ?>
                                    <li>
                                        <span class="meta-label">✉️ Rep Email Address</span>
                                        <span class="meta-value">
                                            <a href="mailto:<?php echo esc_attr($rep_email); ?>">
                                                <?php echo esc_html($rep_email); ?>
                                            </a>
                                        </span>
                                    </li>
                                <?php endif; ?>

                                <?php if ($more_info) : ?>
                                    <li>
                                        <span class="meta-label">ℹ️ Other Guild Info</span>
                                        <span class="meta-value"><?php echo wp_kses_post($more_info); ?></span>
                                    </li>
                                <?php endif; ?>

                            </ul>

                            <?php if ($facebook || $ravelry || $twitter || $pinterest || $instagram || $other_social) : ?>
                            <div class="guild-card__socials">
                                <?php if ($facebook) : ?>
                                    <a href="<?php echo esc_url($facebook); ?>" target="_blank" rel="noopener" class="social-link social-link--facebook">Facebook</a>
                                <?php endif; ?>
                                <?php if ($ravelry) : ?>
                                    <a href="<?php echo esc_url($ravelry); ?>" target="_blank" rel="noopener" class="social-link social-link--ravelry">Ravelry</a>
                                <?php endif; ?>
                                <?php if ($twitter) : ?>
                                    <a href="<?php echo esc_url($twitter); ?>" target="_blank" rel="noopener" class="social-link social-link--twitter">X</a>
                                <?php endif; ?>
                                <?php if ($pinterest) : ?>
                                    <a href="<?php echo esc_url($pinterest); ?>" target="_blank" rel="noopener" class="social-link social-link--pinterest">Pinterest</a>
                                <?php endif; ?>
                                <?php if ($instagram) : ?>
                                    <a href="<?php echo esc_url($instagram); ?>" target="_blank" rel="noopener" class="social-link social-link--instagram">Instagram</a>
                                <?php endif; ?>
                                <?php if ($other_social) : ?>
                                    <a href="<?php echo esc_url($other_social); ?>" target="_blank" rel="noopener" class="social-link social-link--other">Other Social Media</a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                        </div>
                    </article>

                <?php endwhile; ?>

            </div>

        </section>
        <?php

        wp_reset_postdata(); // ← di luar endwhile, di dalam foreach

    endforeach;

    return ob_get_clean();
});


// GUILDS SIDEBAR

add_shortcode('guilds_state_links', function() {
    $states = get_terms([
        'taxonomy'   => 'state',
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);

    if (empty($states) || is_wp_error($states)) return '';

    ob_start();
    ?>
    <nav class="state-nav">
        <ul class="state-nav__list">
            <?php foreach ($states as $state) : ?>
                <li class="state-nav__item">
                    <a href="#state-<?php echo esc_attr($state->slug); ?>" class="state-nav__link">
                        <?php echo esc_html($state->name); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <?php
    return ob_get_clean();
});