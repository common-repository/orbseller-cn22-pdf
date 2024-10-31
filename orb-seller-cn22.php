<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * OrbSeller Amazon Orders
 *
 * @package     OrbSellerCN22
 * @author      Orbital Applications Ltd
 * @copyright   2016 Orbital Applications Ltd
 * @license     GPL-2.0+
 *
 * @orb-seller-cn22
 * Plugin Name: OrbSeller CN22 PDF
 * Plugin URI:  https://orbseller.com/orb-seller-cn22
 * Description: CN22 form for WooCommerce.
 * Version:     1.0
 * Author:      Orbital Applications Ltd
 * Author URI:  https://orbseller.com/orb-seller-cn22
 * Text Domain: orb-seller-cn22
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */



if ( ! class_exists( 'OS_CN22' ) ) :



	class OS_CN22 {

		private $text_domain = 'orb-seller-cn22';

		/**
		 * Construct the plugin.
		 */
		public function __construct() {

			register_activation_hook( __FILE__, array( $this, 'on_activation' ) );

			add_action( 'plugins_loaded', array( $this, 'init' ) );
		}

		/**
		 * Initialize the plugin.
		 * Add Action to Orders Meta box
		 */
		public function init() {

			load_plugin_textdomain( 'orb-seller-cn22', false,
				plugin_basename( dirname( __FILE__ ) . '/localization' ) );

			add_action( 'woocommerce_order_actions', array( $this, 'add_cn22_box_actions' ) );

			add_action( 'woocommerce_order_action_orb_seller_cn_22', array( $this, 'process_cn22_actions' ) );


		}

		/**
		 * Activate Plugin and check WP version and WC is installed
		 */
		public function on_activation() {

			global $wp_version;

			if ( version_compare( $wp_version, '4.6', '<' ) ) {
				wp_die( 'This plugin requires WordPress version 4.6 or higher' );
			}

			if ( ! class_exists( 'WooCommerce' ) ) {
				wp_die( 'This plugin requires WooCommerce version 2.6 upwards.' );
			}

		}


		/*
		 * Add Action to Orders Screen
		 */
		function add_cn22_box_actions( $actions ) {
			$actions['orb_seller_cn_22'] = __( 'Print CN22 Form', $this->text_domain );

			return $actions;
		}

		/*
		 * Generate PDF CN22
		 */
		function process_cn22_actions( $order ) {

			require( dirname( __FILE__ ) . '/includes/libs/tcpdf/tcpdf.php' );

			try {
				$width  = 100;
				$height = 152;
				$pdf    = new TCPDF( PDF_PAGE_ORIENTATION, 'mm', array( $width, $height ), true, 'UTF-8', false );


				$pdf->setPrintHeader( false );
				$pdf->setPrintFooter( false );

				$pdf->SetMargins( 5, 0, 0, true );
				$pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );

				$pdf->AddPage();

				$pdf->SetAutoPageBreak( false, 0 );

				$pdf->Image( plugins_url() . "/orb-seller-cn22/cn22.jpg", 0, 0, 100, 152, 'JPEG', '', 'T', false, 600,
					'',
					false,
					false,
					0,
					'LT' );

				$x           = 0;
				$totalWeight = 0;
				$totalCount  = 0;
				$totalCost   = 0;

				$order_item = $order->get_items();

				$_pf = new WC_Product_Factory();

				$currency_pos = get_option( 'woocommerce_currency_pos' );

				foreach ( $order_item as $product ) {


					if (isset($product['variation_id'])) {
						$_product = $_pf->get_product( $product['variation_id'] );
					} else {
						$_product = $_pf->get_product( $product['product_id'] );
					}



					$weight = 0;
					if ( $_product->has_weight() ) {
						$weight = $_product->get_weight();
					}

					$x ++;

					$totalWeight = ( $totalWeight + $weight ) * $product['qty'];
					$totalCount  = $totalCount + $product['qty'];
					$totalCost   = $totalCost + $product['line_subtotal'];



					switch ( $currency_pos ) {
						case 'left' :
							$html = '<table style="font-size: 12px" width="350" border="1"><tr><td width="205">QTY: ' . $product['qty'] . ' ' . $product['name'] . '  </td><td width="60">' . $weight . 'KG</td><td width="65">' . get_woocommerce_currency_symbol() . number_format( $product['line_subtotal'],
									2 ) . '</td></tr></table>';
							break;
						case 'right' :
							$html = '<table style="font-size: 12px" width="350" border="1"><tr><td width="205">QTY: ' . $product['qty'] . ' ' . $product['name'] . '  </td><td width="60">' . $weight . 'KG</td><td width="65">' . number_format( $product['line_subtotal'],
									2 ) . get_woocommerce_currency_symbol() . '</td></tr></table>';
							break;
						case 'left_space' :
							$html = '<table style="font-size: 12px" width="350" border="1"><tr><td width="205">QTY: ' . $product['qty'] . ' ' . $product['name'] . '  </td><td width="60">' . $weight . 'KG</td><td width="65">' . get_woocommerce_currency_symbol() . ' ' . number_format( $product['line_subtotal'],
									2 ) . get_woocommerce_currency_symbol() . '</td></tr></table>';
							break;
						case 'right_space' :
							$html = '<table style="font-size: 12px" width="350" border="1"><tr><td width="205">QTY: ' . $product['qty'] . ' ' . $product['name'] . '  </td><td width="60">' . $weight . 'KG</td><td width="65">' . number_format( $product['line_subtotal'],
									2 ) . ' ' . get_woocommerce_currency_symbol() . '</td></tr></table>';
							break;
						default:
							$html = '<table style="font-size: 12px" width="350" border="1"><tr><td width="205">QTY: ' . $product['qty'] . ' ' . $product['name'] . '  </td><td width="60">' . $weight . 'KG</td><td width="65">' . get_woocommerce_currency_symbol() . number_format( $product['line_subtotal'],
									2 ) . '</td></tr></table>';
					}

					$pdf->SetX( 30 );
					$pdf->SetY( 30 + ( $x * 5 ) );
					$pdf->writeHTML( $html, true, false, true, false, '' );
				}


				$pdf->writeHTMLCell( 130, 20, 60, 110, '<p style="font-size:12px">' . $totalWeight . 'KG</p>', false,
					false,
					false, false, '' );


				switch ( $currency_pos ) {
					case 'left' :
						$pdf->writeHTMLCell( 130, 20, 80, 110,
							'<p style="font-size:12px">' . get_woocommerce_currency_symbol() . number_format( $totalCost,
								2 ) . '</p>',
							false, false, false,
							false, '' );
						break;
					case 'right' :
						$pdf->writeHTMLCell( 130, 20, 80, 110,
							'<p style="font-size:12px">' . number_format( $totalCost,
								2 ) . get_woocommerce_currency_symbol() . '</p>',
							false, false, false,
							false, '' );
						break;
					case 'left_space' :
						$pdf->writeHTMLCell( 130, 20, 80, 110,
							'<p style="font-size:12px">' . get_woocommerce_currency_symbol() . ' ' . number_format( $totalCost,
								2 ) . '</p>',
							false, false, false,
							false, '' );
						break;
					case 'right_space' :
						$pdf->writeHTMLCell( 130, 20, 80, 110,
							'<p style="font-size:12px">' . number_format( $totalCost,
								2 ) . ' ' . get_woocommerce_currency_symbol() . '</p>',
							false, false, false,
							false, '' );
						break;
					default :
						$pdf->writeHTMLCell( 130, 20, 80, 110,
							'<p style="font-size:12px">' . get_woocommerce_currency_symbol() . number_format( $totalCost,
								2 ) . '</p>',
							false, false, false,
							false, '' );
				}

				$pdf->writeHTMLCell( 130, 20, 40, 130, '<p style="font-size:12px">' . date( 'jS M Y' ) . '</p>', false,
					false, false, false, '' );


				$message = sprintf( __( 'CN22 printed by %s.', 'orb-seller-cn22' ),
					wp_get_current_user()->display_name );
				$order->add_order_note( $message );

// force print dialog
				//$js = 'print(true);';
// set javascript
				//$pdf->IncludeJS( $js );

				header( "Expires: Mon, 26 Jul 2099 05:00:00 GMT" );
				header( "Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . " GMT" );
				header( "Cache-Control: no-store, no-cache, must-revalidate" );
				header( "Cache-Control: post-check=0, pre-check=0", false );
				header( "Pragma: no-cache" );
				header( 'Content-type: application/octet-stream' );
				header( 'Content-Disposition: attachment; filename="cn22_' . $order->id . '.pdf"' );


				$pdf->Output( 'cn22_' . $order->id . '.pdf', 'D' );
				exit();
			} catch ( Exception $e ) {

				$logger = new WC_Logger();

				$logger->add( 'OrbSeller CN22', $e->getMessage() );

				wp_die( 'Sorry, something has gone wrong.' );
			}
		}


	}

	$OS_CN22 = new OS_CN22( __FILE__ );
endif;