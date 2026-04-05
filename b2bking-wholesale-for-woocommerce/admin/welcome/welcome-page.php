<?php
/**
 * B2BKing Core welcome page.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$custom_logo = '';
if ( defined( 'B2BKINGLABEL_DIR' ) && ! empty( get_option( 'b2bking_whitelabel_logo_setting', '' ) ) ) {
	$custom_logo = get_option( 'b2bking_whitelabel_logo_setting', '' );
}

if ( empty( $custom_logo ) && function_exists( 'plugins_url' ) ) {
	$custom_logo = plugins_url( '../../includes/assets/images/logo.png', __FILE__ );
}

$settings_url            = function_exists( 'admin_url' ) ? admin_url( 'admin.php?page=b2bkingcore' ) : '#';
$groups_url              = function_exists( 'admin_url' ) ? admin_url( 'admin.php?page=b2bking_groups' ) : '#';
$docs_url                = 'https://woocommerce-b2b-plugin.com/docs';
$support_url             = 'https://wordpress.org/plugins/b2bking-wholesale-for-woocommerce/';
$wholesale_prices_url    = 'https://woocommerce-b2b-plugin.com/docs/wholesale-prices-how-to-set-different-prices-for-wholesale-buyers/';
$wholesale_discounts_url = 'https://woocommerce-b2b-plugin.com/docs/wholesale-discounts-how-to-apply-a-percentage-discount-on-all-products/';
$payment_methods_doc_url = 'https://woocommerce-b2b-plugin.com/docs/how-to-enable-disable-payment-and-shipping-methods-based-on-users-or-groups/';
$upgrade_url             = 'https://woocommerce-b2b-plugin.com/pricing';
$overview_url            = 'https://kingsplugins.com/woocommerce-wholesale/b2bking/';
?>

<style>
	.notice,
	.update-nag,
	.wrap > .notice {
		display: none !important;
	}

	.b2bking-welcome-preview {
		--b2bking-dark: #15141b;
		--b2bking-dark-soft: #201e2e;
		--b2bking-text: #191821;
		--b2bking-text-soft: #606775;
		--b2bking-text-faint: #8d95a3;
		--b2bking-gold: #c69737;
		--b2bking-gold-deep: rgb(144, 106, 29);
		--b2bking-border: rgba(25, 24, 33, 0.08);
		--b2bking-shadow-lg: 0 26px 70px rgba(20, 24, 33, 0.12);
		--b2bking-shadow-sm: 0 12px 26px rgba(20, 24, 33, 0.06);
		--b2bking-radius-xl: 30px;
		--b2bking-radius-lg: 22px;
		--b2bking-radius-md: 18px;
		background:
			radial-gradient(circle at top, rgba(198, 151, 55, 0.08), transparent 30%),
			linear-gradient(180deg, #f4f6f8, #edf1f4 48%, #eff3f6);
		margin: 20px 20px 0 2px;
		padding: 12px 0 36px;
	}

	.b2bking-welcome-preview *,
	.b2bking-welcome-preview *::before,
	.b2bking-welcome-preview *::after {
		box-sizing: border-box;
	}

	.b2bking-welcome-preview a {
		text-decoration: none;
	}

	.b2bking-welcome-preview__shell {
		max-width: 860px;
		margin: 0 auto;
		border-radius: 34px;
		overflow: hidden;
		background: #fff;
		border: 1px solid rgba(25, 24, 33, 0.06);
		box-shadow: var(--b2bking-shadow-lg);
	}

	.b2bking-welcome-preview__hero {
		position: relative;
		padding: 34px 38px 40px;
		color: #fff;
		text-align: center;
		background:
			radial-gradient(circle at top right, rgba(198, 151, 55, 0.18), transparent 26%),
			linear-gradient(172deg, #15141b, #201e2e, #15141b);
	}

	.b2bking-welcome-preview__logo {
		display: block;
		width: auto;
		max-width: 180px;
		height: auto;
		margin: 0 auto;
	}

	.b2bking-welcome-preview__signature-wrap {
		margin: 14px 0 4px;
		min-height: 76px;
	}

	.b2bking-welcome-preview__signature {
		display: block;
		width: min(320px, 100%);
		height: auto;
		overflow: visible;
		margin: 0 auto;
	}

	.b2bking-welcome-preview__signature text {
		font-family: "Snell Roundhand", "Brush Script MT", "Segoe Script", cursive;
		font-size: 62px;
		font-weight: 400;
		fill: transparent;
		stroke: rgba(250, 241, 223, 0.97);
		stroke-width: 1.3;
		stroke-linecap: round;
		stroke-linejoin: round;
		stroke-dasharray: 1100;
		stroke-dashoffset: 1100;
		filter: drop-shadow(0 14px 24px rgba(198, 151, 55, 0.18));
		animation: b2bking-draw-signature 5.55s ease 0.5s forwards, b2bking-fill-signature 1s ease 4.15s forwards;
	}

	.b2bking-welcome-preview__title {
		width: 100%;
		margin: 6px auto 0;
		font-size: clamp(30px, 4.5vw, 48px);
		line-height: 1.06;
		letter-spacing: -0.04em;
		font-weight: 700;
		color: #fff;
	}

	.b2bking-welcome-preview__copy {
		max-width: 620px;
		margin: 16px auto 0;
		color: rgba(255, 255, 255, 0.76);
		font-size: 16px;
		line-height: 1.8;
	}

	.b2bking-welcome-preview__actions {
		display: flex;
		flex-wrap: wrap;
		justify-content: center;
		gap: 14px;
		margin-top: 24px;
	}

	.b2bking-welcome-preview__button {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		min-height: 48px;
		padding: 0 18px;
		border-radius: 999px;
		border: 1px solid transparent;
		font-size: 14px;
		font-weight: 700;
		letter-spacing: 0.01em;
		transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
	}

	.b2bking-welcome-preview__button:hover,
	.b2bking-welcome-preview__button:focus {
		transform: translateY(-2px);
		outline: none;
	}

	.b2bking-welcome-preview__button--primary {
		color: #15141b;
		background: linear-gradient(180deg, #e5c16e, #c69737);
		box-shadow: 0 14px 28px rgba(198, 151, 55, 0.22);
	}

	.b2bking-welcome-preview__button--primary:hover,
	.b2bking-welcome-preview__button--primary:focus {
		color: #15141b !important;
	}

	.b2bking-welcome-preview__button--secondary {
		color: #fff;
		background: rgba(255, 255, 255, 0.07);
		border-color: rgba(255, 255, 255, 0.14);
	}

	.b2bking-welcome-preview__button--secondary:hover,
	.b2bking-welcome-preview__button--secondary:focus {
		color: #fff !important;
	}

	.b2bking-welcome-preview__surface {
		padding: 34px 38px 38px;
	}

	.b2bking-welcome-preview__proof {
		position: absolute;
		top: 24px;
		right: 24px;
		display: flex;
		justify-content: flex-end;
		margin-top: 0;
	}

	.b2bking-welcome-preview__proof-item {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		min-height: 38px;
		padding: 0 16px;
		border-radius: 999px;
		background: rgba(255, 255, 255, 0.06);
		border: 1px solid rgba(198, 151, 55, 0.18);
		color: rgba(255, 255, 255, 0.7);
		font-size: 12px;
		font-weight: 600;
		letter-spacing: 0.01em;
	}

	.b2bking-welcome-preview__intro {
		margin-bottom: 26px;
	}

	.b2bking-welcome-preview__intro-label {
		display: inline-block;
		margin-bottom: 8px;
		color: var(--b2bking-gold-deep);
		font-size: 12px;
		font-weight: 700;
		letter-spacing: 0.12em;
		text-transform: uppercase;
	}

	.b2bking-welcome-preview__intro h2 {
		margin: 0;
		color: var(--b2bking-text);
		font-size: 30px;
		line-height: 1.12;
		letter-spacing: -0.04em;
	}

	.b2bking-welcome-preview__intro p {
		margin: 12px 0 0;
		max-width: 700px;
		color: var(--b2bking-text-soft);
		font-size: 15px;
		line-height: 1.8;
	}

	.b2bking-welcome-preview__grid {
		display: grid;
		gap: 18px;
	}

	.b2bking-welcome-preview__grid--primary {
		grid-template-columns: 1fr;
	}

	.b2bking-welcome-preview__grid--secondary {
		grid-template-columns: repeat(2, minmax(0, 1fr));
		margin-top: 26px;
	}

	.b2bking-welcome-preview__card {
		padding: 24px;
		border-radius: var(--b2bking-radius-lg);
		background: #fff;
		border: 1px solid var(--b2bking-border);
		box-shadow: var(--b2bking-shadow-sm);
	}

	.b2bking-welcome-preview__card-top {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 16px;
		margin-bottom: 12px;
	}

	.b2bking-welcome-preview__card-step {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 40px;
		height: 40px;
		border-radius: 50%;
		background: linear-gradient(180deg, rgba(198, 151, 55, 0.18), rgba(198, 151, 55, 0.08));
		color: var(--b2bking-gold-deep);
		font-size: 13px;
		font-weight: 700;
	}

	.b2bking-welcome-preview__card-tag {
		color: var(--b2bking-text-faint);
		font-size: 11px;
		font-weight: 700;
		letter-spacing: 0.12em;
		text-transform: uppercase;
	}

	.b2bking-welcome-preview__card h3,
	.b2bking-welcome-preview__mini h3 {
		margin: 0;
		color: var(--b2bking-text);
		font-size: 24px;
		line-height: 1.14;
		letter-spacing: -0.04em;
	}

	.b2bking-welcome-preview__card p,
	.b2bking-welcome-preview__mini p {
		margin: 10px 0 0;
		color: var(--b2bking-text-soft);
		font-size: 14px;
		line-height: 1.8;
	}

	.b2bking-welcome-preview__link {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		margin-top: 16px;
		color: var(--b2bking-gold-deep);
		font-size: 13px;
		font-weight: 700;
	}

	.b2bking-welcome-preview__link::after {
		content: "→";
		font-size: 15px;
	}

	.b2bking-welcome-preview__mini {
		padding: 22px;
		border-radius: var(--b2bking-radius-md);
		background: linear-gradient(180deg, #ffffff, #fafbfc);
		border: 1px solid var(--b2bking-border);
	}

	.b2bking-welcome-preview__resources {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 14px;
		margin-top: 24px;
		padding: 16px 18px;
		border-radius: 18px;
		background: linear-gradient(180deg, #ffffff, #fafbfc);
		border: 1px solid var(--b2bking-border);
		box-shadow: var(--b2bking-shadow-sm);
	}

	.b2bking-welcome-preview__resources-copy {
		display: flex;
		flex-direction: column;
		gap: 4px;
	}

	.b2bking-welcome-preview__resources-label {
		color: var(--b2bking-text);
		font-size: 14px;
		font-weight: 700;
	}

	.b2bking-welcome-preview__resources-subtext {
		color: var(--b2bking-text-soft);
		font-size: 13px;
		line-height: 1.6;
	}

	.b2bking-welcome-preview__resources-actions {
		display: flex;
		flex-wrap: wrap;
		gap: 10px;
	}

	.b2bking-welcome-preview__resource-link {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		min-height: 40px;
		padding: 0 14px;
		border-radius: 999px;
		background: #fff;
		border: 1px solid var(--b2bking-border);
		color: var(--b2bking-text);
		font-size: 13px;
		font-weight: 700;
	}

	.b2bking-welcome-preview__resource-link:hover,
	.b2bking-welcome-preview__resource-link:focus {
		color: var(--b2bking-text) !important;
		transform: translateY(-1px);
		outline: none;
	}

	.b2bking-welcome-preview__dark-band {
		margin-top: 28px;
		padding: 28px;
		border-radius: var(--b2bking-radius-lg);
		color: #fff;
		background:
			radial-gradient(circle at top right, rgba(198, 151, 55, 0.2), transparent 30%),
			linear-gradient(172deg, #15141b, #201e2e, #15141b);
	}

	.b2bking-welcome-preview__dark-band-label {
		display: inline-block;
		margin-bottom: 8px;
		color: #efd7a0;
		font-size: 11px;
		font-weight: 700;
		letter-spacing: 0.12em;
		text-transform: uppercase;
	}

	.b2bking-welcome-preview__dark-band h3 {
		margin: 0;
		font-size: 28px;
		line-height: 1.1;
		letter-spacing: -0.04em;
		color: #fff;
	}

	.b2bking-welcome-preview__dark-band p {
		margin: 12px 0 0;
		max-width: 700px;
		color: rgba(255, 255, 255, 0.74);
		font-size: 15px;
		line-height: 1.8;
	}

	.b2bking-welcome-preview__dark-band-grid {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 14px;
		margin-top: 20px;
	}

	.b2bking-welcome-preview__dark-band-grid .b2bking-welcome-preview__dark-band-item:nth-child(3) {
		grid-column: 1 / -1;
	}

	.b2bking-welcome-preview__dark-band-item {
		display: block;
		padding: 16px;
		border-radius: 16px;
		background: rgba(255, 255, 255, 0.05);
		border: 1px solid rgba(255, 255, 255, 0.08);
		color: inherit;
	}

	.b2bking-welcome-preview__dark-band-item:hover,
	.b2bking-welcome-preview__dark-band-item:focus {
		color: #fff !important;
	}

	.b2bking-welcome-preview__dark-band-item:hover span,
	.b2bking-welcome-preview__dark-band-item:focus span {
		color: rgba(255, 255, 255, 0.78);
	}

	.b2bking-welcome-preview__dark-band-item strong {
		display: block;
		font-size: 15px;
	}

	.b2bking-welcome-preview__dark-band-item span {
		display: block;
		margin-top: 5px;
		color: rgba(255, 255, 255, 0.62);
		font-size: 13px;
		line-height: 1.7;
	}

	.b2bking-welcome-preview__mini-top {
		display: flex;
		align-items: center;
		gap: 14px;
		margin-bottom: 10px;
	}

	.b2bking-welcome-preview__mini-icon {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		border-radius: 14px;
		background: linear-gradient(180deg, rgba(198, 151, 55, 0.16), rgba(198, 151, 55, 0.07));
		color: var(--b2bking-gold-deep);
		flex: 0 0 auto;
	}

	.b2bking-welcome-preview__mini-icon {
		width: 44px;
		height: 44px;
	}

	.b2bking-welcome-preview__mini-icon svg {
		width: 20px;
		height: 20px;
	}

	.b2bking-welcome-preview__upgrade {
		margin-top: 34px;
		padding: 22px 24px;
		border-radius: var(--b2bking-radius-lg);
		background:
			radial-gradient(circle at top right, rgba(255, 255, 255, 0.34), transparent 30%),
			linear-gradient(135deg, #f6df9c, #ddb45d 55%, #c69737);
		border: 1px solid rgba(198, 151, 55, 0.35);
		box-shadow: 0 18px 36px rgba(198, 151, 55, 0.18);
	}

	.b2bking-welcome-preview__upgrade-label {
		display: inline-block;
		margin-bottom: 8px;
		color: rgba(76, 50, 0, 0.86);
		font-size: 11px;
		font-weight: 700;
		letter-spacing: 0.12em;
		text-transform: uppercase;
	}

	.b2bking-welcome-preview__upgrade h3 {
		margin: 0;
		color: #2f2204;
		font-size: 24px;
		line-height: 1.1;
		letter-spacing: -0.04em;
	}

	.b2bking-welcome-preview__upgrade p {
		margin: 10px 0 0;
		max-width: 680px;
		color: rgba(47, 34, 4, 0.82);
		font-size: 14px;
		line-height: 1.7;
	}

	.b2bking-welcome-preview__upgrade-grid {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 12px;
		margin-top: 16px;
	}

	.b2bking-welcome-preview__upgrade-item {
		padding: 12px 14px;
		border-radius: 16px;
		background: rgba(255, 255, 255, 0.35);
		border: 1px solid rgba(103, 73, 10, 0.12);
		color: #2f2204;
	}

	.b2bking-welcome-preview__upgrade-item strong {
		display: block;
		font-size: 14px;
		font-weight: 700;
		line-height: 1.35;
	}

	.b2bking-welcome-preview__upgrade-item span {
		display: block;
		margin-top: 6px;
		font-size: 13px;
		font-weight: 500;
		line-height: 1.55;
		color: rgba(47, 34, 4, 0.82);
	}

	.b2bking-welcome-preview__upgrade-actions {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 14px;
		margin-top: 22px;
	}

	.b2bking-welcome-preview__upgrade-button {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		min-height: 46px;
		padding: 0 18px;
		border-radius: 999px;
		background: linear-gradient(180deg, #2d2413, #15141b);
		color: #fff;
		font-size: 14px;
		font-weight: 700;
		box-shadow: 0 12px 24px rgba(21, 20, 27, 0.18);
		transition: transform 0.2s ease, box-shadow 0.2s ease;
	}

	.b2bking-welcome-preview__upgrade-button:hover,
	.b2bking-welcome-preview__upgrade-button:focus {
		color: #fff !important;
		transform: translateY(-2px);
		outline: none;
	}

	.b2bking-welcome-preview__upgrade-note {
		color: rgba(47, 34, 4, 0.75);
		font-size: 13px;
		font-weight: 600;
	}

	.b2bking-welcome-preview__upgrade-note:hover,
	.b2bking-welcome-preview__upgrade-note:focus {
		color: rgba(47, 34, 4, 0.92) !important;
		outline: none;
	}

	@keyframes b2bking-draw-signature {
		to {
			stroke-dashoffset: 0;
		}
	}

	@keyframes b2bking-fill-signature {
		to {
			fill: rgba(250, 241, 223, 0.98);
			stroke: rgba(250, 241, 223, 0.4);
		}
	}

	@media (max-width: 782px) {
		.b2bking-welcome-preview {
			margin-right: 10px;
		}

		.b2bking-welcome-preview__hero,
		.b2bking-welcome-preview__surface {
			padding: 24px 22px;
		}

		.b2bking-welcome-preview__signature-wrap {
			min-height: 66px;
		}

		.b2bking-welcome-preview__signature text {
			font-size: 60px;
		}

		.b2bking-welcome-preview__proof {
			position: static;
			justify-content: center;
			margin-top: 18px;
		}

		.b2bking-welcome-preview__grid--secondary {
			grid-template-columns: 1fr;
		}

		.b2bking-welcome-preview__dark-band-grid {
			grid-template-columns: 1fr;
		}

		.b2bking-welcome-preview__resources {
			align-items: flex-start;
		}

		.b2bking-welcome-preview__upgrade-grid {
			grid-template-columns: 1fr;
		}
	}

	@media (prefers-reduced-motion: reduce) {
		.b2bking-welcome-preview__button,
		.b2bking-welcome-preview__signature text {
			animation: none;
			transition: none;
		}

		.b2bking-welcome-preview__signature text {
			fill: rgba(250, 241, 223, 0.98);
			stroke-dashoffset: 0;
			stroke: rgba(250, 241, 223, 0.4);
		}
	}
</style>

<div class="wrap b2bking-welcome-preview">
	<div class="b2bking-welcome-preview__shell">
		<section class="b2bking-welcome-preview__hero">
			<?php if ( ! empty( $custom_logo ) ) : ?>
				<img class="b2bking-welcome-preview__logo" src="<?php echo esc_url( $custom_logo ); ?>" alt="<?php esc_attr_e( 'B2BKing', 'b2bking' ); ?>">
			<?php endif; ?>

			<div class="b2bking-welcome-preview__signature-wrap" aria-hidden="true">
				<svg class="b2bking-welcome-preview__signature" viewBox="0 0 440 95" role="presentation">
					<text x="0" y="72">Welcome aboard.</text>
				</svg>
			</div>

			<h1 class="b2bking-welcome-preview__title">
				<?php esc_html_e( 'Thank you for choosing B2BKing.', 'b2bking' ); ?>
			</h1>

			<p class="b2bking-welcome-preview__copy">
				<?php esc_html_e( 'Set up your wholesale store with groups, pricing rules, discounts, and payment or shipping controls for buyers.', 'b2bking' ); ?>
			</p>

			<div class="b2bking-welcome-preview__actions">
				<a class="b2bking-welcome-preview__button b2bking-welcome-preview__button--primary" href="<?php echo esc_url( $settings_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Open Settings', 'b2bking' ); ?>
				</a>
				<a class="b2bking-welcome-preview__button b2bking-welcome-preview__button--secondary" href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Open Documentation', 'b2bking' ); ?>
				</a>
			</div>

			<div class="b2bking-welcome-preview__proof">
				<div class="b2bking-welcome-preview__proof-item">Trusted by 10,000+ active stores</div>
			</div>
		</section>

		<div class="b2bking-welcome-preview__surface">
			<section class="b2bking-welcome-preview__intro">
				<div class="b2bking-welcome-preview__intro-label"><?php esc_html_e( 'Start Here', 'b2bking' ); ?></div>
				<h2><?php esc_html_e( 'Two quick things to get started.', 'b2bking' ); ?></h2>
				<p><?php esc_html_e( 'Choose your store type and create customer groups.', 'b2bking' ); ?></p>
			</section>

			<section class="b2bking-welcome-preview__grid b2bking-welcome-preview__grid--primary">
				<article class="b2bking-welcome-preview__card">
					<div class="b2bking-welcome-preview__card-top">
						<div class="b2bking-welcome-preview__card-step">01</div>
						<div class="b2bking-welcome-preview__card-tag"><?php esc_html_e( 'Essential', 'b2bking' ); ?></div>
					</div>
					<h3><?php esc_html_e( 'Choose your store type', 'b2bking' ); ?></h3>
					<p><?php esc_html_e( 'Choose between a dedicated B2B shop and a B2B & B2C hybrid store.', 'b2bking' ); ?></p>
					<a class="b2bking-welcome-preview__link" href="<?php echo esc_url( $settings_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open settings', 'b2bking' ); ?></a>
				</article>

				<article class="b2bking-welcome-preview__card">
					<div class="b2bking-welcome-preview__card-top">
						<div class="b2bking-welcome-preview__card-step">02</div>
						<div class="b2bking-welcome-preview__card-tag"><?php esc_html_e( 'Foundation', 'b2bking' ); ?></div>
					</div>
					<h3><?php esc_html_e( 'Create customer groups', 'b2bking' ); ?></h3>
					<p><?php esc_html_e( 'Organize buyers into groups to control pricing, payment, and shipping methods.', 'b2bking' ); ?></p>
					<a class="b2bking-welcome-preview__link" href="<?php echo esc_url( $groups_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Manage groups', 'b2bking' ); ?></a>
				</article>
			</section>

			<section class="b2bking-welcome-preview__dark-band">
				<div class="b2bking-welcome-preview__dark-band-label"><?php esc_html_e( 'Next Steps', 'b2bking' ); ?></div>
				<h3><?php esc_html_e( 'Start with these guides.', 'b2bking' ); ?></h3>
				<p><?php esc_html_e( 'Helpful when you are ready for more configuration.', 'b2bking' ); ?></p>
				<div class="b2bking-welcome-preview__dark-band-grid">
					<a class="b2bking-welcome-preview__dark-band-item" href="<?php echo esc_url( $wholesale_prices_url ); ?>" target="_blank" rel="noopener noreferrer">
						<strong><?php esc_html_e( 'Wholesale Prices', 'b2bking' ); ?></strong>
						<span><?php esc_html_e( 'Set wholesale prices for simple and variable products.', 'b2bking' ); ?></span>
					</a>
					<a class="b2bking-welcome-preview__dark-band-item" href="<?php echo esc_url( $wholesale_discounts_url ); ?>" target="_blank" rel="noopener noreferrer">
						<strong><?php esc_html_e( 'Wholesale Discounts', 'b2bking' ); ?></strong>
						<span><?php esc_html_e( 'Apply a percentage discount on all products with a single dynamic rule.', 'b2bking' ); ?></span>
					</a>
					<a class="b2bking-welcome-preview__dark-band-item" href="<?php echo esc_url( $payment_methods_doc_url ); ?>" target="_blank" rel="noopener noreferrer">
						<strong><?php esc_html_e( 'Payment & Shipping Methods', 'b2bking' ); ?></strong>
						<span><?php esc_html_e( 'Control payment and shipping methods by user or group.', 'b2bking' ); ?></span>
					</a>
				</div>
			</section>

			<section class="b2bking-welcome-preview__resources">
				<div class="b2bking-welcome-preview__resources-copy">
					<div class="b2bking-welcome-preview__resources-label"><?php esc_html_e( 'Need help while setting up?', 'b2bking' ); ?></div>
				</div>
				<div class="b2bking-welcome-preview__resources-actions">
					<a class="b2bking-welcome-preview__resource-link" href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Documentation', 'b2bking' ); ?></a>
					<a class="b2bking-welcome-preview__resource-link" href="<?php echo esc_url( $support_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Support', 'b2bking' ); ?></a>
				</div>
			</section>

			<section class="b2bking-welcome-preview__upgrade">
				<div class="b2bking-welcome-preview__upgrade-label"><?php esc_html_e( 'B2BKing Pro', 'b2bking' ); ?></div>
				<h3><?php esc_html_e( 'Go further with Pro.', 'b2bking' ); ?></h3>
				<p><?php esc_html_e( 'With 137+ premium features, Pro takes you from first registration to repeat wholesale orders with smoother business workflows, stronger controls, and more buying flexibility.', 'b2bking' ); ?></p>
				<div class="b2bking-welcome-preview__upgrade-grid">
					<div class="b2bking-welcome-preview__upgrade-item">
						<strong><?php esc_html_e( 'Wholesale buying tools', 'b2bking' ); ?></strong>
						<span><?php esc_html_e( 'Add quote requests, negotiated offers, branded PDFs, wholesale order forms, tiered pricing, CSV ordering, and bulk variations.', 'b2bking' ); ?></span>
					</div>
					<div class="b2bking-welcome-preview__upgrade-item">
						<strong><?php esc_html_e( 'Advanced business workflows', 'b2bking' ); ?></strong>
						<span><?php esc_html_e( 'Add business registration, subaccounts, company order approval, invoice and purchase order gateways, reports, and deeper B2B controls.', 'b2bking' ); ?></span>
					</div>
				</div>
				<div class="b2bking-welcome-preview__upgrade-actions">
					<a class="b2bking-welcome-preview__upgrade-button" href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Upgrade to B2BKing Pro', 'b2bking' ); ?>
					</a>
					<a class="b2bking-welcome-preview__upgrade-note" href="<?php echo esc_url( $overview_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Explore premium features, demos, and pricing.', 'b2bking' ); ?></a>
				</div>
			</section>

		</div>
	</div>
</div>
