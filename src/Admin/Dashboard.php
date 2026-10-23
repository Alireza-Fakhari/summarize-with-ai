<?php
/**
 * Analytics dashboard screen.
 *
 * Markup is rendered server-side; assets/js/admin.js draws the SVG charts
 * from the JSON report embedded in the page (no chart library, no CDN).
 *
 * @package SummarizeWithAI
 */

namespace SummarizeWithAI\Admin;

use SummarizeWithAI\Analytics\Store;
use SummarizeWithAI\Services;
use SummarizeWithAI\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Analytics dashboard screen.
 */
final class Dashboard {

	const RANGES = array( 7, 30, 90 );

	/**
	 * Output the analytics dashboard.
	 */
	public static function render() {
		if ( ! current_user_can( Admin::CAP ) ) {
			return;
		}
		$days = isset( $_GET['range'] ) ? absint( $_GET['range'] ) : 30; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view filter.
		$days = in_array( $days, self::RANGES, true ) ? $days : 30;
		$r    = Store::report( $days );
		$s    = Settings::get();
		$all  = Services::all();

		$t           = $r['totals'];
		$ctr         = $t['views'] ? round( $t['clicks'] / $t['views'] * 100, 1 ) : null;
		$prev_ctr    = $t['prev_views'] ? round( $t['prev_clicks'] / $t['prev_views'] * 100, 1 ) : null;
		$top_service = $r['services'] ? key( $r['services'] ) : '';
		$top_share   = $top_service && $t['clicks'] ? round( reset( $r['services'] ) / $t['clicks'] * 100 ) : 0;
		$has_data    = $t['clicks'] > 0 || $t['views'] > 0;

		$chart = array(
			'labels'   => array_map( array( __CLASS__, 'short_date' ), $r['labels'] ),
			'clicks'   => $r['clicks'],
			'prev'     => $r['prev'],
			'views'    => $r['views'],
			'services' => array(),
			'modes'    => array(),
			'i18n'     => array(
				'clicks'   => __( 'Clicks', 'summarize-with-ai' ),
				'previous' => __( 'Previous period', 'summarize-with-ai' ),
				'total'    => __( 'Total', 'summarize-with-ai' ),
			),
		);
		foreach ( $r['services'] as $id => $hits ) {
			$chart['services'][] = array(
				'name'  => Admin::service_name( $id ),
				'value' => $hits,
				'color' => isset( $all[ $id ] ) ? $all[ $id ]['color'] : '#94a3b8',
			);
		}
		$modes = (array) $s['modes'];
		foreach ( $r['modes'] as $id => $hits ) {
			$chart['modes'][] = array(
				'name'  => isset( $modes[ $id ] ) ? $modes[ $id ]['label'] : ( '' === $id ? __( 'Default', 'summarize-with-ai' ) : $id ),
				'value' => $hits,
			);
		}
		$mobile  = isset( $r['devices']['mobile'] ) ? $r['devices']['mobile'] : 0;
		$desktop = isset( $r['devices']['desktop'] ) ? $r['devices']['desktop'] : 0;
		$dev_sum = max( 1, $mobile + $desktop );
		?>
		<div class="wrap swai-app">
			<h1 class="screen-reader-text"><?php esc_html_e( 'Summarize with AI — Analytics', 'summarize-with-ai' ); ?></h1>
			<?php Admin::header( Admin::SLUG ); ?>

			<div class="swai-toolbar">
				<div>
					<h2 class="swai-title"><?php esc_html_e( 'Overview', 'summarize-with-ai' ); ?></h2>
					<p class="swai-sub">
						<?php
						/* translators: 1: start date, 2: end date. */
						printf( esc_html__( '%1$s – %2$s · compared with the previous period', 'summarize-with-ai' ), esc_html( date_i18n( get_option( 'date_format' ), strtotime( $r['from'] ) ) ), esc_html( date_i18n( get_option( 'date_format' ), strtotime( $r['to'] ) ) ) );
						?>
					</p>
				</div>
				<div class="swai-toolbar__actions">
					<div class="swai-segmented" role="group" aria-label="<?php esc_attr_e( 'Date range', 'summarize-with-ai' ); ?>">
						<?php foreach ( self::RANGES as $range ) : ?>
							<a class="swai-segmented__item<?php echo $range === $days ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'range', $range, admin_url( 'admin.php?page=' . Admin::SLUG ) ) ); ?>"<?php echo $range === $days ? ' aria-current="true"' : ''; ?>>
								<?php
								/* translators: %d: number of days. */
								echo esc_html( sprintf( _n( '%d day', '%d days', $range, 'summarize-with-ai' ), $range ) );
								?>
							</a>
						<?php endforeach; ?>
					</div>
					<a class="swai-btn" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=swai_export_csv&range=' . $days ), 'swai_export_csv' ) ); ?>">
						<?php echo UiIcons::get( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
						<span><?php esc_html_e( 'Export CSV', 'summarize-with-ai' ); ?></span>
					</a>
				</div>
			</div>

			<?php if ( ! $s['analytics'] ) : ?>
				<div class="swai-banner swai-banner--warn">
					<?php echo UiIcons::get( 'info-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
					<span><?php esc_html_e( 'Analytics is turned off, so no new clicks are being recorded.', 'summarize-with-ai' ); ?></span>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Admin::SLUG_SETTING . '#analytics' ) ); ?>"><?php esc_html_e( 'Turn it on', 'summarize-with-ai' ); ?></a>
				</div>
			<?php endif; ?>

			<section class="swai-kpis">
				<?php
				self::kpi( 'mouse', __( 'AI clicks', 'summarize-with-ai' ), number_format_i18n( $t['clicks'] ), Admin::delta( $t['clicks'], $t['prev_clicks'] ), $r['clicks'] );
				if ( $s['track_views'] ) {
					self::kpi( 'monitor', __( 'Box impressions', 'summarize-with-ai' ), number_format_i18n( $t['views'] ), Admin::delta( $t['views'], $t['prev_views'] ), $r['views'] );
					self::kpi( 'activity', __( 'Click-through rate', 'summarize-with-ai' ), null === $ctr ? '—' : number_format_i18n( $ctr, 1 ) . '%', null === $ctr || null === $prev_ctr || ! $prev_ctr ? null : (int) round( ( $ctr - $prev_ctr ) / $prev_ctr * 100 ), array() );
				} else {
					self::kpi_cta();
				}
				self::kpi( 'crown', __( 'Top AI assistant', 'summarize-with-ai' ), $top_service ? Admin::service_name( $top_service ) : '—', null, array(), $top_service ? sprintf( /* translators: %d: percent. */ __( '%d%% of clicks', 'summarize-with-ai' ), $top_share ) : '' );
				?>
			</section>

			<?php if ( ! $has_data ) : ?>
				<section class="swai-card swai-empty">
					<span class="swai-empty__art"><?php echo UiIcons::get( 'graph' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
					<h3><?php esc_html_e( 'No data yet', 'summarize-with-ai' ); ?></h3>
					<p><?php esc_html_e( 'Charts appear as soon as readers start clicking the AI buttons. Open a post to see them in action.', 'summarize-with-ai' ); ?></p>
					<?php
					$latest = get_posts(
						array(
							'numberposts' => 1,
							'post_type'   => $s['post_types'] ? $s['post_types'] : 'post',
							'fields'      => 'ids',
						)
					);
					if ( $latest ) :
						?>
						<a class="swai-btn swai-btn--primary" href="<?php echo esc_url( get_permalink( $latest[0] ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View a post', 'summarize-with-ai' ); ?></a>
					<?php endif; ?>
				</section>
			<?php else : ?>

				<section class="swai-grid swai-grid--main">
					<div class="swai-card">
						<div class="swai-card__head">
							<div>
								<h3><?php esc_html_e( 'Clicks over time', 'summarize-with-ai' ); ?></h3>
								<p><?php esc_html_e( 'Daily clicks on AI buttons', 'summarize-with-ai' ); ?></p>
							</div>
							<ul class="swai-legend">
								<li><i style="background:var(--swai-a)"></i><?php esc_html_e( 'This period', 'summarize-with-ai' ); ?></li>
								<li><i class="is-dashed"></i><?php esc_html_e( 'Previous period', 'summarize-with-ai' ); ?></li>
							</ul>
						</div>
						<div class="swai-chart" data-chart="area" role="img" aria-label="<?php esc_attr_e( 'Line chart of daily clicks', 'summarize-with-ai' ); ?>"></div>
					</div>
					<div class="swai-card">
						<div class="swai-card__head">
							<div>
								<h3><?php esc_html_e( 'By AI assistant', 'summarize-with-ai' ); ?></h3>
								<p><?php esc_html_e( 'Where readers send your content', 'summarize-with-ai' ); ?></p>
							</div>
						</div>
						<div class="swai-donut" data-chart="donut"></div>
					</div>
				</section>

				<section class="swai-grid swai-grid--three">
					<div class="swai-card">
						<div class="swai-card__head"><div><h3><?php esc_html_e( 'Prompt modes', 'summarize-with-ai' ); ?></h3><p><?php esc_html_e( 'What readers ask the AI to do', 'summarize-with-ai' ); ?></p></div></div>
						<div data-chart="bars"></div>
					</div>
					<div class="swai-card">
						<div class="swai-card__head"><div><h3><?php esc_html_e( 'Devices', 'summarize-with-ai' ); ?></h3><p><?php esc_html_e( 'Clicks by screen size', 'summarize-with-ai' ); ?></p></div></div>
						<div class="swai-devices">
							<?php
							foreach (
								array(
									array( 'iphone', __( 'Mobile', 'summarize-with-ai' ), $mobile ),
									array( 'monitor', __( 'Desktop', 'summarize-with-ai' ), $desktop ),
								) as $d
							) :
								$pct = round( $d[2] / $dev_sum * 100 );
								?>
								<div class="swai-device">
									<span class="swai-device__icon"><?php echo UiIcons::get( $d[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
									<div class="swai-device__body">
										<div class="swai-device__row"><strong><?php echo esc_html( $d[1] ); ?></strong><span><?php echo esc_html( number_format_i18n( $pct ) ); ?>%</span></div>
										<div class="swai-progress"><span style="width:<?php echo esc_attr( $pct ); ?>%"></span></div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="swai-card swai-insights">
						<div class="swai-card__head"><div><h3><?php echo UiIcons::get( 'magic' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?><?php esc_html_e( 'Insights', 'summarize-with-ai' ); ?></h3></div></div>
						<ul>
							<?php foreach ( self::insights( $r, $mobile, $desktop ) as $line ) : ?>
								<li><?php echo wp_kses( $line, array( 'strong' => array() ) ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				</section>

				<section class="swai-card">
					<div class="swai-card__head"><div><h3><?php esc_html_e( 'Top posts', 'summarize-with-ai' ); ?></h3><p><?php esc_html_e( 'Content readers most often take to an AI', 'summarize-with-ai' ); ?></p></div></div>
					<table class="swai-table">
						<thead>
							<tr>
								<th scope="col">#</th>
								<th scope="col"><?php esc_html_e( 'Post', 'summarize-with-ai' ); ?></th>
								<th scope="col" class="num"><?php esc_html_e( 'Clicks', 'summarize-with-ai' ); ?></th>
								<?php if ( $s['track_views'] ) : ?>
									<th scope="col" class="num"><?php esc_html_e( 'Impressions', 'summarize-with-ai' ); ?></th>
									<th scope="col" class="num"><?php esc_html_e( 'CTR', 'summarize-with-ai' ); ?></th>
								<?php endif; ?>
								<th scope="col" class="swai-table__bar"><span class="screen-reader-text"><?php esc_html_e( 'Share', 'summarize-with-ai' ); ?></span></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$max = $r['posts'] ? max( 1, $r['posts'][0]['clicks'] ) : 1;
							foreach ( $r['posts'] as $i => $p ) :
								?>
								<tr>
									<td><span class="swai-rank"><?php echo esc_html( number_format_i18n( $i + 1 ) ); ?></span></td>
									<td class="swai-table__title">
										<a href="<?php echo esc_url( $p['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $p['title'] ? $p['title'] : '#' . $p['id'] ); ?></a>
										<?php if ( $p['edit'] ) : ?>
											<a class="swai-table__edit" href="<?php echo esc_url( $p['edit'] ); ?>"><?php esc_html_e( 'Edit', 'summarize-with-ai' ); ?></a>
										<?php endif; ?>
									</td>
									<td class="num"><?php echo esc_html( number_format_i18n( $p['clicks'] ) ); ?></td>
									<?php if ( $s['track_views'] ) : ?>
										<td class="num"><?php echo esc_html( number_format_i18n( $p['views'] ) ); ?></td>
										<td class="num"><?php echo $p['views'] ? esc_html( number_format_i18n( $p['clicks'] / $p['views'] * 100, 1 ) . '%' ) : '—'; ?></td>
									<?php endif; ?>
									<td class="swai-table__bar"><div class="swai-progress"><span style="width:<?php echo esc_attr( round( $p['clicks'] / $max * 100 ) ); ?>%"></span></div></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</section>
			<?php endif; ?>

			<p class="swai-footnote">
				<?php echo UiIcons::get( 'shield-tick' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
				<?php esc_html_e( 'Privacy-first: no cookies, no IP addresses, no personal data. Only anonymous daily totals are stored.', 'summarize-with-ai' ); ?>
			</p>

			<script type="application/json" id="swai-report"><?php echo wp_json_encode( $chart, JSON_HEX_TAG | JSON_HEX_AMP ); ?></script>
		</div>
		<?php
	}

	/**
	 * Output one KPI card.
	 *
	 * @param string   $icon Icon key.
	 * @param string   $label Card label.
	 * @param string   $value Formatted value.
	 * @param int|null $delta Percent change.
	 * @param array    $spark Sparkline values.
	 * @param string   $foot Footer text.
	 */
	private static function kpi( $icon, $label, $value, $delta, $spark, $foot = '' ) {
		?>
		<div class="swai-card swai-kpi">
			<div class="swai-kpi__head">
				<span class="swai-kpi__icon"><?php echo UiIcons::get( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
				<span class="swai-kpi__label"><?php echo esc_html( $label ); ?></span>
			</div>
			<div class="swai-kpi__value"><?php echo esc_html( $value ); ?></div>
			<div class="swai-kpi__foot">
				<?php if ( null !== $delta ) : ?>
					<span class="swai-delta <?php echo $delta >= 0 ? 'is-up' : 'is-down'; ?>">
						<?php echo UiIcons::get( $delta >= 0 ? 'arrow-up' : 'arrow-down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
						<?php echo esc_html( number_format_i18n( abs( $delta ) ) . '%' ); ?>
					</span>
					<span class="swai-muted"><?php esc_html_e( 'vs previous', 'summarize-with-ai' ); ?></span>
				<?php elseif ( $foot ) : ?>
					<span class="swai-muted"><?php echo esc_html( $foot ); ?></span>
				<?php endif; ?>
				<?php if ( $spark ) : ?>
					<span class="swai-spark" data-spark="<?php echo esc_attr( wp_json_encode( array_values( $spark ) ) ); ?>"></span>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Output the card that invites enabling impressions.
	 */
	private static function kpi_cta() {
		?>
		<div class="swai-card swai-kpi swai-kpi--cta">
			<div class="swai-kpi__head">
				<span class="swai-kpi__icon"><?php echo UiIcons::get( 'activity' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></span>
				<span class="swai-kpi__label"><?php esc_html_e( 'Click-through rate', 'summarize-with-ai' ); ?></span>
			</div>
			<p><?php esc_html_e( 'Turn on impression tracking to see impressions and CTR.', 'summarize-with-ai' ); ?></p>
			<a class="swai-link" href="<?php echo esc_url( admin_url( 'admin.php?page=' . Admin::SLUG_SETTING . '#analytics' ) ); ?>"><?php esc_html_e( 'Enable impressions →', 'summarize-with-ai' ); ?></a>
		</div>
		<?php
	}

	/**
	 * Plain-language observations derived from the report.
	 *
	 * @param array $r Report from Store::report().
	 * @param int   $mobile Mobile clicks.
	 * @param int   $desktop Desktop clicks.
	 */
	private static function insights( $r, $mobile, $desktop ) {
		$t   = $r['totals'];
		$out = array();

		$delta = Admin::delta( $t['clicks'], $t['prev_clicks'] );
		if ( null !== $delta ) {
			$out[] = $delta >= 0
				/* translators: %s: percent with <strong>. */
				? sprintf( __( 'AI clicks are up %s compared with the previous period.', 'summarize-with-ai' ), '<strong>' . number_format_i18n( $delta ) . '%</strong>' )
				/* translators: %s: percent with <strong>. */
				: sprintf( __( 'AI clicks are down %s compared with the previous period.', 'summarize-with-ai' ), '<strong>' . number_format_i18n( abs( $delta ) ) . '%</strong>' );
		}
		if ( $r['services'] && $t['clicks'] ) {
			$id    = key( $r['services'] );
			$share = round( reset( $r['services'] ) / $t['clicks'] * 100 );
			/* translators: 1: service name, 2: percent. */
			$out[] = sprintf( __( '%1$s is your readers’ favorite assistant with %2$s of clicks.', 'summarize-with-ai' ), '<strong>' . esc_html( Admin::service_name( $id ) ) . '</strong>', '<strong>' . number_format_i18n( $share ) . '%</strong>' );
		}
		if ( $mobile + $desktop > 0 && $mobile > $desktop ) {
			$out[] = __( 'Most clicks come from <strong>mobile</strong>. Try the floating button for easy thumb access.', 'summarize-with-ai' );
		}
		if ( $r['labels'] ) {
			$best = array_search( max( $r['clicks'] ), $r['clicks'], true );
			if ( max( $r['clicks'] ) > 0 ) {
				/* translators: %s: date. */
				$out[] = sprintf( __( 'Your busiest day was %s.', 'summarize-with-ai' ), '<strong>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $r['labels'][ $best ] ) ) ) . '</strong>' );
			}
		}
		if ( $r['posts'] ) {
			/* translators: %s: post title. */
			$out[] = sprintf( __( '“%s” is the post readers summarize most.', 'summarize-with-ai' ), '<strong>' . esc_html( $r['posts'][0]['title'] ) . '</strong>' );
		}
		return $out;
	}

	/**
	 * Short localized date for chart labels.
	 *
	 * @param string $ymd Date as Y-m-d.
	 */
	public static function short_date( $ymd ) {
		return date_i18n( 'M j', strtotime( $ymd ) );
	}
}
