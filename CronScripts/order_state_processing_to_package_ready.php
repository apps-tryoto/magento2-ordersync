<?php

use Magento\Framework\App\Bootstrap;
require dirname(__FILE__) . '/../../../../../app/bootstrap.php';

$params = $_SERVER;

$bootstrap = Bootstrap::create(BP, $params);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');

$conn	= $objectManager->get('Magento\Framework\App\ResourceConnection');
$db		= $conn->getConnection();

$payment_methods			= ['cashondelivery','phoenix_cashondelivery','msp_cashondelivery','phoenix_bankpayment','bankpayment'];

$tbl_sales_order			= $conn->getTableName('sales_order');
$tbl_sales_order_payment	= $conn->getTableName('sales_order_payment');
$tbl_oto_order_jobs			= $conn->getTableName('oto_order_jobs');
 
$sql = "
	SELECT so.entity_id, so.increment_id, so.state, so.status, so.customer_email, so.created_at, sfop.method, goq.ret_order_completed, goq.ret_invoice_number 
	FROM {$tbl_sales_order} so 
	LEFT JOIN {$tbl_sales_order_payment} sfop ON sfop.parent_id = so.entity_id
	LEFT JOIN {$tbl_oto_order_jobs} goq ON goq.order_id = so.entity_id
	WHERE 
		so.state IN('processing') and so.status NOT IN('package_ready','shipping_ready','shipment_ready')
		AND so.created_at > '".date("Y-m-d H:i:s",strtotime("-30 days"))."' 
		AND goq.ret_order_completed = 'Y'
		AND goq.ret_invoice_number <> ''
	ORDER BY so.entity_id ASC;
	";

print "\n==== PS L:".__LINE__."===============================================\n";
print_r($sql);
print "\n===== EOF PS ==========================================\n";

//$sql = "SELECT * FROM {$tbl_sales_order} so WHERE so.increment_id IN('000014569') ";

$res = $db->query($sql);

$order_model		= $objectManager->get('Magento\Sales\Model\OrderRepository');
$invoiceService		= $objectManager->get('Magento\Sales\Model\Service\InvoiceService');
$transactionService	= $objectManager->get('Magento\Framework\DB\Transaction');

/** @var Magento\Framework\TranslateInterface */
$localeInterface = $objectManager->get('Magento\Framework\TranslateInterface');
$localeInterface->loadData();
// Inizializing \Magento\Framework\Phrase 
\Magento\Framework\Phrase::setRenderer($objectManager->get('Magento\Framework\Phrase\RendererInterface'));

echo date("Y-m-d H:i:s")." -- Sipariş kargo durum kontrolü başladı. ".$res->rowCount()." kayit bulundu.\n";

if ($res->rowCount() < 1) 
{
	die();
} // if sonu


while ($sdata = $res->fetch()) 
{

	$order		= $order_model->get($sdata['entity_id']);

	if (!is_object($order) or $order->getId() < 1) 
	{
		echo "ORDER #".$sdata['order_id']." yuklenemedi !!!\n";
		continue;
	} // if sonu
	
	echo "ORDER #".$order->getIncrementId()." -- CUSTOMER:".$order->getCustomerFirstname()." ".$order->getCustomerLastname();

	try{

		$order->setStatus('package_ready')->save();

		echo " -- Order status is set to Package Ready\n";

		//$this->invoiceSender->send($invoice);
		//Send Invoice mail to customer
		//$order->addStatusHistoryComment(__('Notified customer about invoice creation #%1.', $invoice->getId()))->setIsCustomerNotified(true)->save();
		
	} 
	catch (Exception $e)
	{
		echo " An Error Occured ! -- ".$e->getMessage()."\n";
		continue;
	}
	
} // while sonu

?>