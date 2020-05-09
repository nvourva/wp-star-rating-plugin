<?php
/*
Plugin Name: CI Comment Rating
Description: Adds a star rating system to WordPress comments
Version: 1.0.0
Author: The CSSIgniter Team
Author URI: https://cssigniter.com/
*/

class WP_Star_Rating {

	function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'ci_comment_rating_styles' ) );

		add_action( 'comment_form_logged_in_after', array( $this, 'ci_comment_rating_rating_field' ) );
		add_action( 'comment_form_after_fields', array( $this, 'ci_comment_rating_rating_field' ) );

		add_action( 'comment_post', array( $this, 'ci_comment_rating_save_comment_rating' ) );

		add_filter( 'preprocess_comment', array( $this, 'ci_comment_rating_require_rating' ) );

		add_filter( 'comment_text', array( $this, 'ci_comment_rating_display_rating' ) );

		add_filter( 'the_content', array( $this, 'ci_comment_rating_display_average_rating' ) );
	}

	//Enqueue the plugin's styles.
	function ci_comment_rating_styles() {

		wp_register_style( 'ci-comment-rating-styles', plugins_url( '/', __FILE__ ) . 'assets/style.css' );

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'ci-comment-rating-styles' );
	}

	//Create the rating interface.
	function ci_comment_rating_rating_field () {
		?>
		<label for="rating">Rating<span class="required">*</span></label>
		<fieldset class="comments-rating">
			<span class="rating-container">
				<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
					<input type="radio" id="rating-<?php echo esc_attr( $i ); ?>" name="rating" value="<?php echo esc_attr( $i ); ?>" /><label for="rating-<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></label>
				<?php endfor; ?>
				<input type="radio" id="rating-0" class="star-cb-clear" name="rating" value="0" /><label for="rating-0">0</label>
			</span>
		</fieldset>
		<?php
	}

	//Save the rating submitted by the user.
	function ci_comment_rating_save_comment_rating( $comment_id ) {
		if ( ( isset( $_POST['rating'] ) ) && ( '' !== $_POST['rating'] ) ) {
			$rating = intval( $_POST['rating'] );
			add_comment_meta( $comment_id, 'rating', $rating );
			$postid = get_comment( $comment_id )->comment_post_ID;
			
			if ( metadata_exists('post', $postid, 'post_average_rating') ) {
				update_post_meta( $postid, 'post_average_rating', $this->ci_comment_rating_get_average_ratings( $postid ) );
			} else {
				add_post_meta( $postid, 'post_average_rating', $this->ci_comment_rating_get_average_ratings( $postid ), true );
			}
		}
	}

	//Make the rating required.
	function ci_comment_rating_require_rating( $commentdata ) {
		if ( ! is_admin() && ( ! isset( $_POST['rating'] ) || 0 === intval( $_POST['rating'] ) ) )
		wp_die( __( 'Error: You did not add a rating. Hit the Back button on your Web browser and resubmit your comment with a rating.' ) );
		return $commentdata;
	}

	//Display the rating on a submitted comment.
	function ci_comment_rating_display_rating( $comment_text ){

		if ( $rating = get_comment_meta( get_comment_ID(), 'rating', true ) ) {
			$stars = '<p class="stars">';
			for ( $i = 1; $i <= $rating; $i++ ) {
				$stars .= '<span class="dashicons dashicons-star-filled"></span>';
			}
			$stars .= '</p>';
			$comment_text = $comment_text . $stars;
			return $comment_text;
		} else {
			return $comment_text;
		}
	}

	//Get the average rating of a post.
	function ci_comment_rating_get_average_ratings( $id ) {
		$comments = get_approved_comments( $id );

		if ( $comments ) {
			$i = 0;
			$total = 0;
			foreach( $comments as $comment ){
				$rate = get_comment_meta( $comment->comment_ID, 'rating', true );
				if( isset( $rate ) && '' !== $rate ) {
					$i++;
					$total += $rate;
				}
			}

			if ( 0 === $i ) {
				return false;
			} else {
				return round( $total / $i, 1 );
			}
		} else {
			return false;
		}
	}

	//Display the average rating above the content.
	function ci_comment_rating_display_average_rating( $content ) {

		global $post;

		if ( false === $this->ci_comment_rating_get_average_ratings( $post->ID ) ) {
			return $content;
		}
		
		$stars   = '';
		$average = $this->ci_comment_rating_get_average_ratings( $post->ID );

		for ( $i = 1; $i <= $average + 1; $i++ ) {
			
			$width = intval( $i - $average > 0 ? 20 - ( ( $i - $average ) * 20 ) : 20 );

			if ( 0 === $width ) {
				continue;
			}

			$stars .= '<span style="overflow:hidden; width:' . $width . 'px" class="dashicons dashicons-star-filled"></span>';

			if ( $i - $average > 0 ) {
				$stars .= '<span style="overflow:hidden; position:relative; left:-' . $width .'px;" class="dashicons dashicons-star-empty"></span>';
			}
		}
		
		$custom_content  = '<p class="average-rating">This post\'s average rating is: ' . $average .' ' . $stars .'</p>';
		$custom_content .= $content;
		return $custom_content;
	}
}

new WP_Star_Rating();