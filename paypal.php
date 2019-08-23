<?php

namespace abdelrahmannourallah\paypal;

use yii;
use yii\base\Component;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Details;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;

/*
 * You cannot re-use this code without contact Abdelrahman Nourallah
 */

/**
 * Description of epay
 *
 * @author abdelrahman nourallah <info@ip4t.net>
 */
class PayPal extends Component {

    public $client_key;
    public $client_secret, $paypal;
    public function init()
    {
        $this->setConfig();
    }

    private function setConfig(){
            $this->paypal = new \PayPal\Rest\ApiContext( new \PayPal\Auth\OAuthTokenCredential($this->client_key , $this->client_secret ));
    }

    public function payRequest($items = [], $shipping, $paymentMethod = 'paypal', $currency = 'GBP') {
        $total = 0;
        $subTotal = 0;

        $payer = new \PayPal\Api\Payer();
        $payer->setPaymentMethod($paymentMethod);

        $itemsArray= [];
        foreach ($items as $k => $v) {
            $item = new Item();
            $item->setName($v['product'])->setCurrency($currency)->setQuantity(1)->setPrice($v['price']);
            $subTotal += $v['price'];
            $total += $v['price'];
            $itemsArray[] = $item;
        }
        $total += $shipping;
        $itemList = new ItemList();
        $itemList->setItems($itemsArray);

        $details = New Details();
        $details->setShipping($shipping)->setSubtotal($subTotal);

        $amount = new Amount();
        $amount->setCurrency($currency)->setTotal($total)->setDetails($details);
        $transaction = new Transaction();
        $transaction->setAmount($amount)->setItemList($itemList)->setDescription('payForSomthing Payment')
                ->setInvoiceNumber(uniqid());

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl(SITE_URL . '/pay.php?succees=true')->setCancelUrl(SITE_URL . 'pay.php?success=false');

        $payment = new Payment();

        $payment->setIntent('sale')->setPayer($payer)->setRedirectUrls($redirectUrls)->setTransactions([$transaction]);


        try {
            $approvalUrl = $payment->create($this->paypal);
            $approvalUrl = $payment->getApprovalLink();
            header("Location: {$approvalUrl}");
        } catch (Exception $e) {
            die($e);
        }
    }

}
