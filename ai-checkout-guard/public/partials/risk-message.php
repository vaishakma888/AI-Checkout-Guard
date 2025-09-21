    <?php
/**
 * Partial: risk-message.php
 *
 * Outputs a styled risk message based on the decision.
 *
 * Variables expected in scope:
 *   - $decision: array with keys 'tier', 'score', 'reason'
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tier   = isset( $decision['tier'] ) ? sanitize_text_field( $decision['tier'] ) : 'medium';
$score  = isset( $decision['score'] ) ? absint( $decision['score'] ) : 50;
$reason = isset( $decision['reason'] ) ? esc_html( $decision['reason'] ) : '';

$classes = 'ai-guard-risk-notice ' . esc_attr( $tier );
?>

<div class="<?php echo $classes; ?>">
	<strong>
		<?php
		echo esc_html( sprintf(
			/* translators: %s: risk tier */
			__( 'Risk Level: %s', 'ai-checkout-guard' ),
			ucfirst( $tier )
		) );
		?>
	</strong>
	<p>
		<?php
		echo esc_html( sprintf(
			/* translators: %d: risk score */
			__( 'Score: %d', 'ai-checkout-guard' ),
			$score
		) );
		?>
	</p>
	<?php if ( $reason ) : ?>
		<p class="ai-guard-reason">
			<?php
			echo esc_html( sprintf(
				/* translators: %s: reason text */
				__( 'Reason: %s', 'ai-checkout-guard' ),
				$reason
			) );
			?>
		</p>
	<?php endif; ?>
</div>
