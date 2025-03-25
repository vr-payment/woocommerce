<?php
/**
 * Plugin Name: VRPayment
 * Author: VR Payment GmbH
 * Text Domain: vrpayment
 * Domain Path: /languages/
 *
 * VRPayment
 * This plugin will add support for all VRPayment payments methods and connect the VRPayment servers to your WooCommerce webshop (https://www.vr-payment.de/).
 *
 * @category Class
 * @package  VRPayment
 * @author   VR Payment GmbH (https://www.vr-payment.de)
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

defined( 'ABSPATH' ) || exit;

/**
 * This service handles webhooks.
 */
class WC_VRPayment_Service_Webhook extends WC_VRPayment_Service_Abstract {

	const VRPAYMENT_MANUAL_TASK = 1487165678181;
	const VRPAYMENT_PAYMENT_METHOD_CONFIGURATION = 1472041857405;
	const VRPAYMENT_TRANSACTION = 1472041829003;
	const VRPAYMENT_DELIVERY_INDICATION = 1472041819799;
	const VRPAYMENT_TRANSACTION_INVOICE = 1472041816898;
	const VRPAYMENT_TRANSACTION_COMPLETION = 1472041831364;
	const VRPAYMENT_TRANSACTION_VOID = 1472041867364;
	const VRPAYMENT_REFUND = 1472041839405;
	const VRPAYMENT_TOKEN = 1472041806455;
	const VRPAYMENT_TOKEN_VERSION = 1472041811051;

	/**
	 * The webhook listener API service.
	 *
	 * @var \VRPayment\Sdk\Service\WebhookListenerService
	 */
	private $webhook_listener_service;

	/**
	 * The webhook url API service.
	 *
	 * @var \VRPayment\Sdk\Service\WebhookUrlService
	 */
	private $webhook_url_service;


	/**
	 * Webhook entities.
	 *
	 * @var array
	 */
	private $webhook_entities = array();

	/**
	 * Construct.
	 *
	 * Constructor to register the webhook entites.
	 */
	public function __construct() {
		$this->init_webhook_entities();
	}

	/**
	 * Initializes webhook entities with their specific configurations.
	 */
	private function init_webhook_entities() {
		$this->webhook_entities[ self::VRPAYMENT_MANUAL_TASK ] = new WC_VRPayment_Webhook_Entity(
			self::VRPAYMENT_MANUAL_TASK,
			'Manual Task',
			array(
				\VRPayment\Sdk\Model\ManualTaskState::DONE,
				\VRPayment\Sdk\Model\ManualTaskState::EXPIRED,
				\VRPayment\Sdk\Model\ManualTaskState::OPEN,
			),
			'WC_VRPayment_Webhook_Manual_Task'
		);
		$this->webhook_entities[ self::VRPAYMENT_PAYMENT_METHOD_CONFIGURATION ] = new WC_VRPayment_Webhook_Entity(
			self::VRPAYMENT_PAYMENT_METHOD_CONFIGURATION,
			'Payment Method Configuration',
			array(
				\VRPayment\Sdk\Model\CreationEntityState::ACTIVE,
				\VRPayment\Sdk\Model\CreationEntityState::DELETED,
				\VRPayment\Sdk\Model\CreationEntityState::DELETING,
				\VRPayment\Sdk\Model\CreationEntityState::INACTIVE,
			),
			'WC_VRPayment_Webhook_Method_Configuration',
			true
		);
		$this->webhook_entities[ self::VRPAYMENT_TRANSACTION ] = new WC_VRPayment_Webhook_Entity(
			self::VRPAYMENT_TRANSACTION,
			'Transaction',
			array(
				\VRPayment\Sdk\Model\TransactionState::CONFIRMED,
				\VRPayment\Sdk\Model\TransactionState::AUTHORIZED,
				\VRPayment\Sdk\Model\TransactionState::DECLINE,
				\VRPayment\Sdk\Model\TransactionState::FAILED,
				\VRPayment\Sdk\Model\TransactionState::FULFILL,
				\VRPayment\Sdk\Model\TransactionState::VOIDED,
				\VRPayment\Sdk\Model\TransactionState::COMPLETED,
				\VRPayment\Sdk\Model\TransactionState::PROCESSING,
			),
			'WC_VRPayment_Webhook_Transaction'
		);
		$this->webhook_entities[ self::VRPAYMENT_DELIVERY_INDICATION ] = new WC_VRPayment_Webhook_Entity(
			self::VRPAYMENT_DELIVERY_INDICATION,
			'Delivery Indication',
			array(
				\VRPayment\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED,
			),
			'WC_VRPayment_Webhook_Delivery_Indication'
		);

		$this->webhook_entities[ self::VRPAYMENT_TRANSACTION_INVOICE ] = new WC_VRPayment_Webhook_Entity(
			self::VRPAYMENT_TRANSACTION_INVOICE,
			'Transaction Invoice',
			array(
				\VRPayment\Sdk\Model\TransactionInvoiceState::NOT_APPLICABLE,
				\VRPayment\Sdk\Model\TransactionInvoiceState::PAID,
				\VRPayment\Sdk\Model\TransactionInvoiceState::DERECOGNIZED,
			),
			'WC_VRPayment_Webhook_Transaction_Invoice'
		);

		$this->webhook_entities[ self::VRPAYMENT_TRANSACTION_COMPLETION ] = new WC_VRPayment_Webhook_Entity(
			self::VRPAYMENT_TRANSACTION_COMPLETION,
			'Transaction Completion',
			array(
				\VRPayment\Sdk\Model\TransactionCompletionState::FAILED,
				\VRPayment\Sdk\Model\TransactionCompletionState::SUCCESSFUL,
			),
			'WC_VRPayment_Webhook_Transaction_Completion'
		);

		$this->webhook_entities[ self::VRPAYMENT_TRANSACTION_VOID ] = new WC_VRPayment_Webhook_Entity(
			self::VRPAYMENT_TRANSACTION_VOID,
			'Transaction Void',
			array(
				\VRPayment\Sdk\Model\TransactionVoidState::FAILED,
				\VRPayment\Sdk\Model\TransactionVoidState::SUCCESSFUL,
			),
			'WC_VRPayment_Webhook_Transaction_Void'
		);

		$this->webhook_entities[ self::VRPAYMENT_REFUND ] = new WC_VRPayment_Webhook_Entity(
			self::VRPAYMENT_REFUND,
			'Refund',
			array(
				\VRPayment\Sdk\Model\RefundState::FAILED,
				\VRPayment\Sdk\Model\RefundState::SUCCESSFUL,
			),
			'WC_VRPayment_Webhook_Refund'
		);
		$this->webhook_entities[ self::VRPAYMENT_TOKEN ] = new WC_VRPayment_Webhook_Entity(
			self::VRPAYMENT_TOKEN,
			'Token',
			array(
				\VRPayment\Sdk\Model\CreationEntityState::ACTIVE,
				\VRPayment\Sdk\Model\CreationEntityState::DELETED,
				\VRPayment\Sdk\Model\CreationEntityState::DELETING,
				\VRPayment\Sdk\Model\CreationEntityState::INACTIVE,
			),
			'WC_VRPayment_Webhook_Token'
		);
		$this->webhook_entities[ self::VRPAYMENT_TOKEN_VERSION ] = new WC_VRPayment_Webhook_Entity(
			self::VRPAYMENT_TOKEN_VERSION,
			'Token Version',
			array(
				\VRPayment\Sdk\Model\TokenVersionState::ACTIVE,
				\VRPayment\Sdk\Model\TokenVersionState::OBSOLETE,
			),
			'WC_VRPayment_Webhook_Token_Version'
		);
	}

	/**
	 * Installs the necessary webhooks in VRPayment.
	 */
	public function install() {
		$space_id = get_option( WooCommerce_VRPayment::VRPAYMENT_CK_SPACE_ID );
		if ( ! empty( $space_id ) ) {
			$webhook_url = $this->get_webhook_url( $space_id );
			if ( null == $webhook_url ) {
				$webhook_url = $this->create_webhook_url( $space_id );
			}
			$existing_listeners = $this->get_webhook_listeners( $space_id, $webhook_url );
			foreach ( $this->webhook_entities as $webhook_entity ) {
				/* @var WC_VRPayment_Webhook_Entity $webhook_entity */ //phpcs:ignore
				$exists = false;
				foreach ( $existing_listeners as $existing_listener ) {
					if ( $existing_listener->getEntity() == $webhook_entity->get_id() ) {
						$exists = true;
					}
				}
				if ( ! $exists ) {
					$this->create_webhook_listener( $webhook_entity, $space_id, $webhook_url );
				}
			}
		}
	}

	/**
	 * Get the webhook entity for a specific ID or throws an exception if not found.
	 *
	 * @param mixed $id The ID of the webhook entity to retrieve.
	 * @return WC_VRPayment_Webhook_Entity The webhook entity associated with the given ID.
	 * @throws Exception If the webhook entity cannot be found.
	 */
	public function get_webhook_entity_for_id( $id ) {
		if ( ! isset( $this->webhook_entities[ $id ] ) ) {
			throw new Exception( sprintf( 'Could not retrieve webhook model for listener entity id: %s', esc_attr( $id ) ) );
		}
		return $this->webhook_entities[ $id ];
	}

	/**
	 * Create a webhook listener.
	 *
	 * @param WC_VRPayment_Webhook_Entity $entity entity.
	 * @param int $space_id space id.
	 * @param \VRPayment\Sdk\Model\WebhookUrl $webhook_url webhook url.
	 *
	 * @return \VRPayment\Sdk\Model\WebhookListenerCreate
	 * @throws \Exception Exception.
	 */
	protected function create_webhook_listener( WC_VRPayment_Webhook_Entity $entity, $space_id, \VRPayment\Sdk\Model\WebhookUrl $webhook_url ) {
		$webhook_listener = new \VRPayment\Sdk\Model\WebhookListenerCreate();
		$webhook_listener->setEntity( $entity->get_id() );
		$webhook_listener->setEntityStates( $entity->get_states() );
		$webhook_listener->setName( 'Woocommerce ' . $entity->get_name() );
		$webhook_listener->setState( \VRPayment\Sdk\Model\CreationEntityState::ACTIVE );
		$webhook_listener->setUrl( $webhook_url->getId() );
		$webhook_listener->setNotifyEveryChange( $entity->is_notify_every_change() );
		$webhook_listener->setEnablePayloadSignatureAndState( true );
		return $this->get_webhook_listener_service()->create( $space_id, $webhook_listener );
	}

	/**
	 * Returns the existing webhook listeners.
	 *
	 * @param int $space_id space id.
	 * @param \VRPayment\Sdk\Model\WebhookUrl $webhook_url webhook url.
	 *
	 * @return \VRPayment\Sdk\Model\WebhookListener[]
	 * @throws \Exception Exception.
	 */
	protected function get_webhook_listeners( $space_id, \VRPayment\Sdk\Model\WebhookUrl $webhook_url ) {
		$query = new \VRPayment\Sdk\Model\EntityQuery();
		$filter = new \VRPayment\Sdk\Model\EntityQueryFilter();
		$filter->setType( \VRPayment\Sdk\Model\EntityQueryFilterType::_AND );
		$filter->setChildren(
			array(
				$this->create_entity_filter( 'state', \VRPayment\Sdk\Model\CreationEntityState::ACTIVE ),
				$this->create_entity_filter( 'url.id', $webhook_url->getId() ),
			)
		);
		$query->setFilter( $filter );
		return $this->get_webhook_listener_service()->search( $space_id, $query );
	}

	/**
	 * Creates a webhook url.
	 *
	 * @param int $space_id space id.
	 *
	 * @return \VRPayment\Sdk\Model\WebhookUrlCreate
	 * @throws \Exception Exception.
	 */
	protected function create_webhook_url( $space_id ) {
		$webhook_url = new \VRPayment\Sdk\Model\WebhookUrlCreate();
		$webhook_url->setUrl( $this->get_url() );
		$webhook_url->setState( \VRPayment\Sdk\Model\CreationEntityState::ACTIVE );
		$webhook_url->setName( 'Woocommerce' );
		return $this->get_webhook_url_service()->create( $space_id, $webhook_url );
	}

	/**
	 * Returns the existing webhook url if there is one.
	 *
	 * @param int $space_id space id.
	 *
	 * @return \VRPayment\Sdk\Model\WebhookUrl
	 * @throws \Exception Exception.
	 */
	protected function get_webhook_url( $space_id ) {
		$query = new \VRPayment\Sdk\Model\EntityQuery();
		$filter = new \VRPayment\Sdk\Model\EntityQueryFilter();
		$filter->setType( \VRPayment\Sdk\Model\EntityQueryFilterType::_AND );
		$filter->setChildren(
			array(
				$this->create_entity_filter( 'state', \VRPayment\Sdk\Model\CreationEntityState::ACTIVE ),
				$this->create_entity_filter( 'url', $this->get_url() ),
			)
		);
		$query->setFilter( $filter );
		$query->setNumberOfEntities( 1 );
		try {
			$result = $this->get_webhook_url_service()->search( $space_id, $query );
			if ( ! empty( $result ) ) {
				return $result[0];
			} else {
				return null;
			}
		} catch ( \Exception $e ) {
			WooCommerce_VRPayment::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
		}
	}

	/**
	 * Returns the webhook endpoint URL.
	 *
	 * @return string
	 */
	protected function get_url() {
		return add_query_arg( 'wc-api', 'vrpayment_webhook', home_url( '/' ) );
	}

	/**
	 * Returns the webhook listener API service.
	 *
	 * @return \VRPayment\Sdk\Service\WebhookListenerService
	 * @throws \Exception Exception.
	 */
	protected function get_webhook_listener_service() {
		if ( null == $this->webhook_listener_service ) {
			$this->webhook_listener_service = new \VRPayment\Sdk\Service\WebhookListenerService( WC_VRPayment_Helper::instance()->get_api_client() );
		}
		return $this->webhook_listener_service;
	}

	/**
	 * Returns the webhook url API service.
	 *
	 * @return \VRPayment\Sdk\Service\WebhookUrlService
	 * @throws \Exception Exception.
	 */
	protected function get_webhook_url_service() {
		if ( null == $this->webhook_url_service ) {
			$this->webhook_url_service = new \VRPayment\Sdk\Service\WebhookUrlService( WC_VRPayment_Helper::instance()->get_api_client() );
		}
		return $this->webhook_url_service;
	}
}
