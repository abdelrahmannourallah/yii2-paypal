<?php

namespace abdelrahmannourallah\paypal;

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\PaymentExecution;
use yii\base\Component;

/*
 * You can not redistribution  this code without contact Abdelrahman Nourallah
 */

/**
 * Description of Paypal
 *
 * @author abdelrahman nourallah <info@ip4t.net>
 * AMMAN - Jordan
 */
class PayPal extends Component
{

    const PAYMENT_METHOD_PAYPAL = 'paypal';
    const PAYMENT_METHOD_CREADIT_CARD = 'credit_card';
    const PAYMENT_METHOD_PAY_UPON_INVOICE = 'pay_upon_invoice';
    const PAYMENT_METHOD_CARRIER = 'carrier';
    const PAYMENT_METHOD_ALTERNATE_PAYMENT = 'alternate_payment';
    const PAYMENT_METHOD_BANK = 'bank';

    public $client_key, $client_secret;
    public $currency, $invoiceNumber, $intent, $returnUrl, $cancelUrl, $paymentMethod;

    private $_paypal;
    public function init()
    {
        $this->setConfig();
    }

    private function setConfig()
    {
        $this->_paypal = new \PayPal\Rest\ApiContext(new \PayPal\Auth\OAuthTokenCredential($this->client_key, $this->client_secret));
    }

    public function payRequest($items = [], $shipping, $invoiceNumber, $description)
    {

        $this->invoiceNumber = ($invoiceNumber !== null ? $invoiceNumber : uniqid());

        $total = 0;
        $subTotal = 0;

        $itemsArray = [];
        $item = new Item();
          foreach ($items as $k => $v) {
        $item = new Item();
        $item->setName($v['product_name'])->setCurrency($this->currency)->setQuantity($v['quantity'])->setPrice($v['price']);
        $subTotal += $v['price'] * $v['quantity'];
        $itemsArray[] = $item;
        } 


        $total = $subTotal + $shipping;
   
        $payer = new Payer();
        $payer->setPaymentMethod($this->paymentMethod);

        $itemList = new ItemList();
        $itemList->setItems($itemsArray);

        $details = new Details();

        $details->setShipping($shipping)->setSubtotal($subTotal);

        $amount = new Amount();
        $amount->setCurrency($this->currency)->setTotal($total)->setDetails($details);
        $transaction = new Transaction();
        $transaction->setAmount($amount)->setItemList($itemList)->setDescription($description)
            ->setInvoiceNumber(uniqid());
        
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($this->returnUrl)->setCancelUrl($this->cancelUrl);
     
        $payment = new Payment();
        $payment->setIntent('sale')->setPayer($payer)->setRedirectUrls($redirectUrls)->setTransactions([$transaction]);
       
        try {
         
            $approvalUrl = $payment->create($this->_paypal);
            $approvalUrl = $payment->getApprovalLink();
            \Yii::$app->getResponse()->redirect($approvalUrl);
            
 
        } catch (PayPalConnectionException $ex) {
            // This will print the detailed information on the exception.
            //REALLY HELPFUL FOR DEBUGGING
            \Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
            \Yii::$app->response->data = $ex->getData();
        }
    }

    public function PayResult()
    {
        if (!\Yii::$app->request->get('paymentId') || !\Yii::$app->request->get('PayerID')) {
            die('request missed !!');
        }
        $paymentId = $_GET['paymentId'];
        $payerId = $_GET['PayerID'];

        $payment = Payment::get($paymentId, $this->_paypal);
        $excute = new PaymentExecution();
        $excute->setPayerId($payerId);
        try {

            $response = $payment->execute($excute, $this->_paypal);
            return $response;
            
        } catch (PayPalConnectionException $ex) {
            // This will print the detailed information on the exception.
            //REALLY HELPFUL FOR DEBUGGING
            \Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
            \Yii::$app->response->data = $ex->getData();
        }
    }

    public function setReturnUrl($url)
    {
        $this->returnUrl = $url;
    }
    public function setCancelUrl($url)
    {
        $this->cancelUrl = $url;
    }
    public function setPaymentMethod($value)
    {
        $this->paymentMethod =  strtolower($value);
    }
}
