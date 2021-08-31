<?php
/**
 * CC WordPress Plugin: Image Class
 *
 * @package CC_WordPress_Plugin
 * @subpackage Image_Class
 * @since 2.0
 */

/**
 * Class to extract common EXIF tags from an image.
 *
 * @package Image_Class
 */
class CreativeCommonsImage {

	/**
	 * Instance
	 *
	 * @var null $instance
	 */
	private static $instance = null;


	/**
	 * Constructor
	 */
	private function __construct() {
	}

	/**
	 * Function: get_instance
	 *
	 * @return $instance
	 */
	public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Function: exif_copyright_license_url
	 *
	 * Extract the first license in triangle brackets from the Exif Copyright
	 * FIXME: validate in regex, and handle publicdomain
	 *
	 * @param mixed $copyright exif copyright.
	 * @return string URL for the license (or empty if not found).
	 */
	public function exif_copyright_license_url( $copyright ) {
		$url = '';
		$matched = preg_match(
			'/<(https?:\/\/creativecommons.org\/licenses\/[^>]+)>/',
			$copyright,
			$matches
		);
		if ( ! $matched ) {
			$matched = preg_match(
				'/<(https?:\/\/creativecommons.org\/publicdomain\/[^>]+)>/',
				$copyright,
				$matches
			);
		}
		if ( $matched ) {
			$url = $matches[1];
		}
		return $url;
	}


	/**
	 * Function: exif_url
	 *
	 * Extracts a URL from a string of the form "A. N. Other <https://another.com/home/>"
	 *
	 * @param  mixed $exif_value a string of the form "A. N. Other <https://another.com/home/>".
	 * @return string URL extracted from $exif_value.
	 */
	public function exif_url( $exif_value ) {
		$url     = '';
		$matched = preg_match( '/<(https?:\/\/[^>]+)>/', $exif_value, $matches );
		if ( $matched ) {
			$url = trim( $matches[1] );
		}
		return $url;
	}


	/**
	 * Function: exif_text
	 *
	 * Extracts the non-URL text from a string of the form "A. N. Other <https://another.com/home/>"
	 *
	 * @param  mixed $exif_value a string of the form "A. N. Other <https://another.com/home/>".
	 * @return string Non-URL text extracted from $exif_value.
	 */
	public function exif_text( $exif_value ) {
		return trim( preg_replace( '/<https?:\/\/[^>]+>/', '', $exif_value ) );
	}


	/**
	 * Function: license_button_url
	 *
	 * Convert a license url into the url for the icon for that license
	 *
	 * @param  mixed $license_url URL of the given licence.
	 * @return string URL for the license button.
	 */
	public function license_button_url( $license_url ) {
		$url     = false;
		$matched = preg_match(
			'/\/(licenses|publicdomain)\/([^\/]+)\//',
			$license_url,
			$matches
		);
		// SHOULD BE ENUMERATIVE.
		if ( $matched ) {
			if ( 'zero' == $matches[2] ) {
				$url = CCPLUGIN__URL . 'includes/attribution-box/cc0.svg';
			} elseif ( 'publicdomain' == $matches[1] ) {
				$url = CCPLUGIN__URL . 'includes/attribution-box/cc0.svg';
			} else {
				$url = CCPLUGIN__URL . 'includes/attribution-box/'
					. $matches[2]
					. '.svg';
			}
		}
		return $url;
	}


	/**
	 * Function: license_name
	 *
	 * Generates the canonical English name for the license with the given url
	 *
	 * @param  mixed $license_url URL of the given license.
	 * @return string License name.
	 */
	public function license_name( $license_url ) {
		$name = '';
		if ( strpos( $license_url, '/publicdomain/' ) ) {
			// Note combination of version with dedication.
			if ( strpos( $license_url, '/zero/1.0' ) ) {
				$name = 'CC0 1.0 Universal';
			} else {
				$name = 'Public Domain';
			}
		} else {
			$name = 'Creative Commons Attribution';
			if ( strpos( $license_url, '-nc' ) !== false ) {
				$name .= '-NonCommercial';
			}
			if ( strpos( $license_url, '-nd' ) !== false ) {
				$name .= '-NoDerivatives';
			} elseif ( strpos( $license_url, '-sa' ) !== false ) {
				$name .= '-ShareAlike';
			}

			if ( strpos( $license_url, '/2.0/' ) !== false ) {
				$name .= ' 2.0 Generic';
			} elseif ( strpos( $license_url, '/2.5/' ) !== false ) {
				$name .= ' 2.5 Generic';
			} elseif ( strpos( $license_url, '/3.0/' ) !== false ) {
				$name .= ' 3.0 Unported';
			} elseif ( strpos( $license_url, '/4.0/' ) !== false ) {
				$name .= ' 4.0 International';
			}
			$name .= ' License';
		}
		return $name;
	}


	/**
	 * Function: license_url_is_zero
	 *
	 * @param  mixed $license_url URL of the given license.
	 * @return bool Returns true if detected license is in the public domain.
	 */
	public function license_url_is_zero( $license_url ) {
		return strpos(
			$license_url,
			'//creativecommons.org/publicdomain/zero/1.0/'
		) !== false;
	}


	/**
	 * Function: maybe_apply_attachment_license_url
	 *
	 * Using now EXIF/IPTC fields from WordPress' own wp_read_image_metadata()
	 * instead of PHP's built-in exif_read_data().
	 *
	 * @param  mixed $post_id id of given post.
	 * @param  mixed $exif Associative array of parsed EXIF/IPTC fields.
	 * @return void
	 */
	public function maybe_apply_attachment_license_url( $post_id, $exif ) {
		if ( isset( $exif['copyright'] ) ) {
			$url = $this->exif_copyright_license_url( $exif['copyright'] );
			// Set the metadata, which wasn't already set.
			add_post_meta( $post_id, 'license_url', $url, true );
		}
	}


	/**
	 * Function: maybe_apply_attachment_url
	 *
	 * Note that this ought to work even with the fields coming from wp_read_image_metadata()
	 * and not only from PHP's built-in exif_read_data().
	 *
	 * @param  mixed $post_id WordPress post ID.
	 * @param  mixed $meta_field Metadata field for this attachment.
	 * @param  mixed $exif Associative array of parsed EXIF/IPTC fields.
	 * @param  mixed $exif_field EXIF/IPTC field name.
	 * @return void
	 */
	public function maybe_apply_attachment_url( $post_id, $meta_field, $exif, $exif_field ) {

		if ( isset( $exif [ $exif_field ] ) ) {
			$url = $this->exif_url( $exif [ $exif_field ] );
			// Set the metadata, which wasn't already set.
			add_post_meta( $post_id, $meta_field, $url, true );
		}
	}


	/**
	 * Function read_exif
	 *
	 * Get exif for given image format.
	 *
	 * Replaced exif_read_data() with the WP function wp_read_image_metadata() which also covers IPTC
	 * (gwyneth 20210830).
	 *
	 * @link https://developer.wordpress.org/reference/functions/wp_read_image_metadata/
	 * @todo Read XMP as well; WordPress (up to 5.8) doesn't provide a function for that yet.
	 *
	 * @param  mixed $post_id id of given post.
	 * @return mixed Either the retrieved image metadata extracted from an existing and valid image, or null.
	 */
	public function read_exif( $post_id ) {

		// Will give error for image formats we can't get Exif for.
		$image_path = get_attached_file( $post_id );
		// WordPress added a more sophisticated function, handling IPTC data as well (gwyneth 20210830).
		// was: $exif       = exif_read_data( $image_path ); .

		$exif = null; // set to null here, because, scope (gwyneth 20210831).

		// First check if the relevant functions are available; if not, get them from `image.php`.
		// @link https://developer.wordpress.org/reference/functions/wp_read_image_metadata/#comment-2123 .
		if ( ! function_exists( 'file_is_valid_image' ) || ! function_exists( 'wp_read_image_metadata' ) ) {
			require_once ABSPATH . '/wp-admin/includes/image.php';
		}

		if ( file_is_valid_image( $image_path ) ) {
			// Note that this $exif has different fields than those from exif_read_data()!
			$exif = wp_read_image_metadata( $image_path );
		}

		return $exif;
	}


	/**
	 * Function: extract_exif_license_metadata
	 *
	 * If the file has Exif, and it cointains license metadata, apply it
	 *
	 * @param  mixed $post_id id of given post.
	 * @return void
	 */
	public function extract_exif_license_metadata( $post_id ) {
		$exif = $this->read_exif( $post_id );
		$this->maybe_apply_attachment_license_url( $post_id, $exif );
		$this->maybe_apply_attachment_url(
			$post_id,
			'attribution_url',
			$exif,
			'ImageDescription'
		);
	}


	/**
	 * Function: license_url_field_id
	 *
	 * @param  mixed $post_id id of the given post.
	 * @return string Tag attribute including the post id.
	 */
	public function license_url_field_id( $post_id ) {
		return "attachments-{$post_id}-license_url]"; // why the closing square bracket (gwyneth 20210831)?
	}


	/**
	 * Function: license_select
	 *
	 * @param  mixed $post_id id of the given post.
	 * @param  mixed $original Original license.
	 * @return string HTML for the license <select> tag.
	 */
	public function license_select( $post_id, $original ) {

		// TODO: Select a better option than "Original" if we can determine one.
		// TODO: CC0.
		// FIXME: Move style to separate css file and change to separate js file.

		$select = '<select onChange="var el = document.getElementById(\''
				. $this->license_url_field_id( $post_id )
				// Set & scroll to end of text field so user can see new value.
				. '\'); el.value=this.value; el.scrollLeft = el.scrollWidth;"'
				. 'style="vertical-align: baseline;">';
		$select .= "<option value=\"{$original}\">"
				. __( 'Original' )
				. '</option>';
		// Short names so the select fits on the same line as the text input.
		$select .= '<option value="https://creativecommons.org/licenses/by/4.0/">' . __( 'CC BY 4.0' ) . '</option>';
		$select .= '<option value="https://creativecommons.org/licenses/by-nc/4.0/">' . __( 'CC BY-NC 4.0' ) . '</option>';
		$select .= '<option value="https://creativecommons.org/licenses/by-nc-nd/4.0/">' . __( 'CC BY-NC-ND 4.0' ) . '</option>';
		$select .= '<option value="https://creativecommons.org/licenses/by-nc-sa/4.0/">' . __( 'CC BY-NC-SA 4.0' ) . '</option>';
		$select .= '<option value="https://creativecommons.org/licenses/by-nd/4.0/">' . __( 'CC BY-ND 4.0' ) . '</option>';
		$select .= '<option value="https://creativecommons.org/licenses/by-sa/4.0/">' . __( 'CC BY-SA 4.0' ) . '</option>';
		$select .= '<option value="https://creativecommons.org/publicdomain/zero/1.0/">' . __( 'CC0' ) . '</option>';
		$select .= '<option value="">' . __( 'None' ) . '</option>';
		$select .= '</select>';
		return $select;
	}


	/**
	 * Function: license_text_field
	 *
	 * @param mixed $post_id id of given post.
	 * @param mixed $original Original license.
	 * @return string HTML-formatted text for the input field.
	 */
	public function license_text_field( $post_id, $original ) {
		return '<input type="text" class="text"'
			. " name=\"attachments[{$post_id}][license_url]\""
			. ' id="' . $this->license_url_field_id( $post_id ) . '"'
			. " value=\"{$original}\""
			. ' />';
	}


	/**
	 * Function: add_image_license_metadata
	 *
	 * Adds license metadata to an image, via a form.
	 *
	 * @param mixed $form_fields Associative array of form field names.
	 * @param mixed $post WP post object.
	 * @return mixed Updated form fields.
	 */
	public function add_image_license_metadata( $form_fields, $post ) {
		$post_id = $post->ID;

		// FIXME: We can get the attribution name from Artist, and title from ImageDescription. Should we?

		$original_license = get_post_meta( $post_id, 'license_url', true );

		$form_fields['license_url'] = array(
			'label' => __( 'License&nbsp;URL' ),
			'input' => 'html',
			'value' => $original_license,
			'helps' => __( 'The URL for the license for the work, e.g. https://creativecommons.org/licenses/by-sa/4.0/ .<br />Only change this to correct the license or if you are the rightsholder!' ),
			'html'  => $this->license_text_field( $post_id, $original_license )
					. $this->license_select(
						$post_id,
						$original_license
					),
		);

		$attachment_metadata = wp_get_attachment_metadata( $post_id, true );
		$attribution_name    = get_post_meta( $post_id, 'attribution_name', true );

		if ( isset( $attachment_metadata['image_meta'] ) ) {
			$image_metadata = $attachment_metadata['image_meta'];
			if ( ( ! $attribution_name )
				&& isset( $image_metadata['credit'] )
			) {
				$attribution_name = $image_metadata['credit'];
			}
		}

		$form_fields['attribution_name'] = array(
			'label' => __( 'Attribution Name' ),
			'input' => 'text',
			'value' => $attribution_name,
			'helps' => __( 'The name to attribute the work to, e.g. A. N. Other' ),
		);

		$form_fields['attribution_url'] = array(
			'label' => __( 'Attribution&nbsp;URL' ),
			'input' => 'text',
			'value' => get_post_meta( $post_id, 'attribution_url', true ),
			'helps' => __( "The URL to which the work should be attributed. For example the work's page on the author's site., e.g. https://example.com/mattl/image2/" ),
		);

		$form_fields['source_work_url'] = array(
			'label' => __( 'Source&nbsp;Work' ),
			'input' => 'text',
			'value' => get_post_meta( $post_id, 'source_work_url', true ),
			'helps' => __( 'The URL of the work that this work is based on or derived from, e.g. https://example.com/robm/image1/' ),
		);

		$form_fields['extra_permissions_url'] = array(
			'label' => __( 'Extra&nbsp;Permissions' ),
			'input' => 'text',
			'value' => get_post_meta( $post_id, 'extra_permissions_url', true ),
			'helps' => __( 'A URL where the user can find information about obtaining rights that are not already permitted by the CC license, e.g. https://example.com/mattl/image2/ccplus/' ),
		);

		return $form_fields;
	}


	/**
	 * Function: save_image_license_metadata
	 *
	 * Adds license metadata for an image attached to a post.
	 *
	 * @param mixed $post WP post object.
	 * @param mixed $attachment WP attachment object.
	 * @return mixed Updated WP post object with license metadata.
	 */
	public function save_image_license_metadata( $post, $attachment ) {
		$image_meta = array( 'license_url', 'attribution_url', 'source_work_url', 'extra_permissions_url' );
		foreach ( $image_meta as $field ) {
			if ( isset( $attachment[ $field ] ) ) {
				update_post_meta(
					$post['ID'],
					$field,
					esc_url( $attachment[ $field ] )
				);
			}
		}

		$image_meta_no_sanitization = array( 'attribution_name' );
		foreach ( $image_meta_no_sanitization as $field ) {
			if ( isset( $attachment[ $field ] ) ) {
				update_post_meta(
					$post['ID'],
					$field,
					// Documentation says not to sanitize.
					$attachment[ $field ]
				);
			}
		}
		return $post;
	}

	/**
	 * Function: license_block
	 *
	 * @param mixed $att_id ID for the attribute.
	 * @param mixed $fallback_title Default title when nothing had been explicitly set.
	 * @return string HTML-formatted block for the license.
	 */
	public function license_block( $att_id, $fallback_title = null ) {
		if ( null === $fallback_title ) {
			$fallback_title = __( 'This image' );
		}
		$license_url     = get_post_meta( $att_id, 'license_url', true );
		$license_url     = strtolower( $license_url );
		$attribution_url = get_post_meta( $att_id, 'attribution_url', true );
		$source_work_url = get_post_meta( $att_id, 'source_work_url', true );
		$extras_url      = get_post_meta( $att_id, 'extra_permissions_url', true );

		// Unfiltered.
		$meta   = wp_get_attachment_metadata( $att_id, true );
		$credit = get_post_meta( $att_id, 'attribution_name', true );
		if ( ( ! $credit )
			&& isset( $meta['credit'] )
		) {
			$credit = $meta['image_meta']['credit'];
		}

		$title = get_the_title( $att_id );
		if ( ! $title ) {
			$title = $meta['image_meta']['title'];
		}
		if ( ! $title ) {
			$title = $fallback_title;
		}
		if ( strpos( $license_url, 'creativecommons' ) ) {
			if ( substr( $license_url, -1 ) != '/' ) {
				$license_url = $license_url . '/';
			}
		}

		if ( $license_url ) {
			$license_name = $this->license_name( $license_url );
			$button_url   = $this->license_button_url( $license_url );
		}

		// RDF stuff.

		if ( $license_url ) {
			$license_button_url = $this->license_button_url( $license_url );
			$l                  = CreativeCommons::get_instance();
			$html_rdfa = $l->license_html_rdfa(
				$license_url,
				$license_name,
				$license_button_url,
				$title,
				true, // is_singular.
				$attribution_url,
				$credit,
				$source_work_url,
				$extras_url,
				''
			); // warning_text.

			$button = CreativeCommonsButton::get_instance()->markup(
				$html_rdfa,
				false,
				31,
				false
			);

			$block  = $button;
			$block .= '<!-- RDFa! -->' . $html_rdfa . '<!-- end of RDFa! -->';
		} else {
			if ( $title ) {
				if ( $credit ) {
					$block .= '<p>( ' . $title . ' by ' . $credit . ')</p>';
				} else {
					$block .= '<p>( ' . $title . ')</p>';
				}
			}
		}

		return $block;
	}


	/**
	 * Function: caption_block
	 *
	 * @param mixed $attr Attributes.
	 * @param mixed $att_id Attachment ID.
	 * @return string HTML for the caption.
	 */
	public function caption_block( $attr, $att_id ) {

		// This is way too simple. Improve before re-introducing the caption filter.
		$caption = '<div class="cc-license-caption-wrapper cc-license-block">'
				. '<div class="wp-caption-text">'
				. $attr['caption']
				. '</div><br />';

		$caption .= $this->license_block( $att_id, $attr['caption'] );

		$caption .= '</div>';

		return $caption;
	}


	/**
	 * Function: image_url_to_postid
	 *
	 * This will make two database calls for a resized image, and almost every embedded
	 * image will be resized. We do this to avoid the edge case where image-20x20.jpg and image.jpg
	 * both exist and we are getting the former.
	 *
	 * @param mixed $image_url URL to the attached image.
	 * @return Resulting post id for this image, or null if not found.
	 */
	public function image_url_to_postid( $image_url ) {
		$att_id = null;
		$att_id = attachment_url_to_postid( $image_url );
		if ( ! $att_id ) {
			// Remove resized image part of path.
			// attachment_url_to_postid doesn't handle that.
			// Make sure regex handles image urls that end e.g. aaa-1.jpg.
			$image_url = preg_replace(
				'/-\d+x\d+(\.[^.]+)$/i',
				'$1',
				$image_url
			);
			$att_id    = attachment_url_to_postid( $image_url );
		}
		return $att_id;
	}

	/**
	 * Function: license_shortcode
	 *
	 * @param mixed $atts Apparently unused parameter (?).
	 * @param mixed $content HTML for the License Block shortcode.
	 * @return mixed Parsed shortcode text.
	 */
	public function license_shortcode( $atts, $content = null ) {

		if ( null !== $content ) {
			// TODO: Profile replacing this with parsing html and walking the DOM.
			$match_count = preg_match(
				'/<img[^>]+src="([^"]+)"/',
				$content,
				$matches
			);
			if ( 1 == $match_count ) {
				$image_url = $matches[1];
				$att_id    = $this->image_url_to_postid( $image_url );
				if ( $att_id ) {
					$content .= '<div class="cc-license-block"><br />';
					$content .= $this->license_block( $att_id );
					$content .= '</div>';
				}
			}
		}
		return do_shortcode( $content );
	}

	/**
	 * Function: captioned_image
	 *
	 * Note that we use isset(license), so e.g. license="1" and license="true"
	 * both work. We could allow users to insert a license *here*, but we would
	 * rather associate the license with the media object as that is more
	 * robust. So we do not and will not do that.
	 *
	 * @param mixed $empty Unused parameter.
	 * @param mixed $attr Shortcode attributes to use.
	 * @param mixed $content Actual shortcode content.
	 * @return string Formatted HTML for the shortcode, or empty string if id/license not set.
	 */
	public function captioned_image( $empty, $attr, $content ) {
			$args = shortcode_atts(
				array(
					'id'      => '',
					'align'   => 'alignnone',
					'width'   => '',
					'caption' => '',
					'title'   => '',
				),
				$attr
			);

		if ( isset( $attr['id'] )
			&& isset( $attr['license'] )
		) {
			// Extract attachment $post->ID.
			preg_match( '/\d+/', $attr['id'], $att_id );
			if ( $att_id ) {

				// We *should* handle this based on the shortcode code's behaviour.
				// if ((intval($width) > 1) && $caption) {.
				$result = '<div ' /*. $id*/ . 'class="cc-caption wp-caption '
						. esc_attr( $args['align'] ) . '"'
						// . ' style="width: ' . (10 + (int) $width) . 'px"'
						. '>' . do_shortcode( $content )
						. $this->caption_block( $attr, $att_id[0] )
						. '</div>';
				// }
			}
		} else {
			$result = '';
		}
		return $result;
	}

	/**
	 * Function: init
	 *
	 * @return void
	 */
	public function init() {
		add_filter(
			'add_attachment',
			array( $this, 'extract_exif_license_metadata' ),
			0,
			2
		);

		add_filter(
			'attachment_fields_to_edit',
			array( $this, 'add_image_license_metadata' ),
			10,
			2
		);

		add_filter(
			'attachment_fields_to_save',
			array( $this, 'save_image_license_metadata' ),
			10,
			2
		);

		// We really need to improve our emulation of the caption shortcode's
		// output, and make sure our css fits it better, before adding this
		// back in.

		/*
		 * add_filter(
		 * 'img_caption_shortcode',
		 * array($this, 'captioned_image'),
		 * 10,
		 * 3
		 * );
		*/

		if ( get_option( 'enable_attribution_box' ) ) {
			add_filter( 'the_content', array( $this, 'add_attribution_boxes' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_attribution_boxes' ) );
		}

		add_shortcode(
			'license',
			array( $this, 'license_shortcode' )
		);
	}

	/**
	 * Function: enqueue_attribution_boxes
	 *
	 * @return void
	 */
	public function enqueue_attribution_boxes() {
		wp_enqueue_style( 'cc-attribution-box',
			plugins_url( 'css/cc-attribution-box.css', dirname( __FILE__ ) ),
			array(),
			filemtime( get_template_directory() . 'css/cc-attribution-box.css' ) // cache-buster (gwyneth 20210831)!
		);
	}

	/**
	 * Function: add_attribution_boxes
	 *
	 * Adds attribution boxes to all instances of `wp-image-*` using a regex.
	 *
	 * @param mixed $content Additional HTML content for the attribution boxes.
	 * @return mixed Replacement result.
	 */
	public function add_attribution_boxes( $content ) {
		return preg_replace_callback(
			'/<img\s[^>]*class="wp-image-(\d+)[^>]*>/',
			function ( $matches ) {
				return '<div class="cc-attribution-box-container">'
					. $matches[0]
					. '<div class="cc-attribution-box"> '
					. $this->simple_license_block( $matches[1] )
					. '</div></div>';
			},
			$content
		);
	}

	/**
	 * Function: simple_license_block
	 *
	 * @param mixed $att_id Attachment id.
	 * @return string HTML-formatted license block.
	 */
	public function simple_license_block( $att_id ) {
		/**
		 * TODO: this should just be replaced by just using $this->license_block.
		 * Need to make sure that code can be safely fixed for this purpose,
		 */
		$license_url     = get_post_meta( $att_id, 'license_url', true );
		$attribution_url = get_post_meta( $att_id, 'attribution_url', true );
		$title           = get_the_title( $att_id );
		$credit          = get_post_meta( $att_id, 'attribution_name', true );

		$license_name = $this->license_name( $license_url );
		$button_url   = $this->license_button_url( $license_url );

		if ( empty( $button_url ) ) {
			return ''; // empty() will check for both null and ''.
		}

		$lazy = function_exists( 'wp_lazy_loading_enabled' ) && wp_lazy_loading_enabled( 'img', 'simple_license_block' );
		$loading_type = $lazy ? 'lazy' : 'eager'; // 'eager' is the browser default.
		return '<div>' . esc_html( $credit ) . '</div>'
			. "<a target='_blank' href='" . esc_url( $attribution_url ) . "'>$title</a>"
			. "<a class='cc-attribution-box-license' target='_blank' href='" . esc_url( $license_url ) . "' title='"
			. esc_attr( $license_name ) . "'>"
			. "<img src='" . esc_url( $button_url ) . "' alt='" . esc_attr( $license_name )
			. " . loading='" . $loading_type . "'></a>";
	}

}
