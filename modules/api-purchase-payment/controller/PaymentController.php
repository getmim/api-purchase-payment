<?php
/**
 * PaymentController
 * @package api-purchase-payment
 * @version 0.0.1
 */

namespace ApiPurchasePayment\Controller;

use Purchase\Model\Purchase;
use PurchasePayment\Model\PurchasePayment as PPayment;
use LibFormatter\Library\Formatter;
use LibForm\Library\Form;
use LibUser\Library\Fetcher;

class PaymentController extends \Api\Controller
{
    protected string $error;

    protected function getPurchase()
    {
        $cond = [
            'id' => $this->req->param->id
        ];

        if ($this->user->isLogin())
            $cond['user'] = $this->user->id;
        elseif($user = $this->req->get('user'))
            $cond['user'] = $user;

        if (!isset($cond['user'])) {
            $this->error = 'Required `user` field is not set';
            return null;
        }

        $purchase = Purchase::getOne($cond);
        if (!$purchase) {
            $this->error = 'Purchase data not found';
            return null;
        }

        return $purchase;
    }

    public function createAction()
    {
        if (!$this->app->isAuthorized())
            return $this->resp(401);

        $purchase = $this->getPurchase();
        if (!$purchase) {
            return $this->resp(400, $this->error);
        }

        if ($purchase->status != 1) {
            return $this->resp(404);
        }

        $form = new Form('api-purchase-payment.create');
        if (!($valid = $form->validate())) {
            return $this->resp(422, $form->getErrors());
        }

        $handlers = $this->config->purchasePayment->handlers;
        $handler = $valid->handler;
        $meth_id = $valid->id;

        if (!isset($handlers->$handler)) {
            return $this->resp(400, 'Handler not found');
        }

        $handler = $handlers->$handler;

        if (!$handler::exists($purchase, $valid->id)) {
            return $this->resp(400, 'Payment method with that id not found');
        }

        $payment_fee = $handler::getFee($purchase, $valid->id);
        $total = $purchase->total + $payment_fee;
        $payment = (object)[
            'purchase' => $purchase->id,
            'method'   => null,
            'fee'      => $payment_fee,
            'total'    => $total
        ];

        $result = $handler::create($purchase, $total, $valid->id);
        if (is_null($result)) {
            return $this->resp(500, $handler::lastError());
        }

        $payment->method = json_encode($result);

        $payment_id = PPayment::create((array)$payment);

        Purchase::set(['status' => 2], ['id' => $purchase->id]);

        $payment = PPayment::getOne(['id' => $payment_id]);
        $payment = Formatter::format('purchase-payment', $payment, ['purchase']);

        if (isset($payment->method->meta)) {
            unset($payment->method->meta);
        }

        return $this->resp(0, $payment);
    }

    public function instructionAction()
    {
        if (!$this->app->isAuthorized())
            return $this->resp(401);

        $purchase = $this->getPurchase();
        if (!$purchase) {
            return $this->resp(400, $this->error);
        }

        if ($purchase->status != 2) {
            return $this->resp(0, []);
        }

        $handlers = $this->config->purchasePayment->handlers;
        if (!$handlers) {
            return $this->resp(500, 'No payment handler registered');
        }

        $payment = PPayment::getOne(['purchase' => $purchase->id]);
        $payment->method = json_decode($payment->method);

        $inst = null;
        foreach ($handlers as $handler) {
            $inst = $handler::getInstruction($purchase, $payment);
            if ($inst) {
                break;
            }
        }

        $this->resp(0, $inst);
    }

    public function methodAction()
    {
        if (!$this->app->isAuthorized())
            return $this->resp(401);

        $purchase = $this->getPurchase();
        if (!$purchase) {
            return $this->resp(400, $this->error);
        }

        if ($purchase->status != 1) {
            return $this->resp(0, []);
        }

        $handlers = $this->config->purchasePayment->handlers;
        if (!$handlers) {
            return $this->resp(500, 'No payment handler registered');
        }

        $methods = [];
        foreach ($handlers as $name => $class) {
            $result = $class::getMethods($purchase);
            if (is_null($result)) {
                return $this->resp(500, $class::lastError());
            }

            foreach ($result as $group => &$mes) {
                foreach ($mes as &$me) {
                    $me['handler'] = $name;
                }
                unset($me);
            }

            $methods = array_merge_recursive($methods, $result);
        }

        return $this->resp(0, $methods);
    }

    public function singleAction()
    {
        if (!$this->app->isAuthorized())
            return $this->resp(401);

        $purchase = $this->getPurchase();
        if (!$purchase) {
            return $this->resp(400, $this->error);
        }

        $payment = PPayment::getOne(['purchase' => $purchase->id]);
        if (!$payment) {
            return $this->resp(404);
        }

        $payment = Formatter::format('purchase-payment', $payment, ['purchase']);
        if (isset($payment->method->meta))
            unset($payment->method->meta);

        return $this->resp(0, $payment);
    }
}
