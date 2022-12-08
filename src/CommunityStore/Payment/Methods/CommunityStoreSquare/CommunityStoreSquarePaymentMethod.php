<?php
namespace Concrete\Package\CommunityStoreSquare\Src\CommunityStore\Payment\Methods\CommunityStoreSquare;

use Concrete\Package\CommunityStore\Controller\SinglePage\Dashboard\Store;
use Core;
use Log;
use Config;
use Exception;
use Square\SquareClient;
use Square\Environment;
use Square\Models;
use Square\Exceptions\ApiException;

use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as StorePaymentMethod;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Calculator as StoreCalculator;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;
use \SquareConnect\Api\TransactionApi as TransactionApi;

class CommunityStoreSquarePaymentMethod extends StorePaymentMethod
{

    public function dashboardForm()
    {
        $this->set('squareMode', Config::get('community_store_square.mode'));
        $this->set('squareCurrency',Config::get('community_store_square.currency'));
        $this->set('squareSandboxApplicationId',Config::get('community_store_square.sandboxApplicationId'));
        $this->set('squareSandboxAccessToken',Config::get('community_store_square.sandboxAccessToken'));
        $this->set('squareSandboxLocation',Config::get('community_store_square.sandboxLocation'));
        $this->set('squareLiveApplicationId',Config::get('community_store_square.liveApplicationId'));
        $this->set('squareLiveAccessToken',Config::get('community_store_square.liveAccessToken'));
        $this->set('squareLiveLocation',Config::get('community_store_square.liveLocation'));
        $this->set('form',Core::make("helper/form"));

        $gateways = array(
            'square_form'=>'Form'
        );

        $this->set('squareGateways',$gateways);

        $currencies = array(
        	'USD'=>t('US Dollars'),
        	'CAD'=>t('Canadian Dollar')
        );

        $this->set('squareCurrencies',$currencies);
    }

    public function save(array $data = [])
    {
        Config::save('community_store_square.mode',$data['squareMode']);
        Config::save('community_store_square.currency',$data['squareCurrency']);
        Config::save('community_store_square.sandboxApplicationId',$data['squareSandboxApplicationId']);
        Config::save('community_store_square.sandboxAccessToken',$data['squareSandboxAccessToken']);
        Config::save('community_store_square.sandboxLocation',$data['squareSandboxLocation']);
        Config::save('community_store_square.liveApplicationId',$data['squareLiveApplicationId']);
        Config::save('community_store_square.liveAccessToken',$data['squareLiveAccessToken']);
        Config::save('community_store_square.liveLocation',$data['squareLiveLocation']);
    }
    public function validate($args,$e)
    {
        return $e;
    }
    public function checkoutForm()
    {
        $mode = Config::get('community_store_square.mode');
        $this->set('mode',$mode);
        $this->set('currency',Config::get('community_store_square.currency'));

        if ($mode == 'live') {
            $this->set('publicAPIKey',Config::get('community_store_square.liveApplicationId'));
        } else {
            $this->set('publicAPIKey',Config::get('community_store_square.sandboxApplicationId'));
        }

        $customer = new StoreCustomer();

        $this->set('email', $customer->getEmail());
        $this->set('form', Core::make("helper/form"));
        $this->set('amount', number_format(StoreCalculator::getGrandTotal() * 100, 0, '', ''));

        $pmID = StorePaymentMethod::getByHandle('community_store_square')->getID();
        $this->set('pmID',$pmID);
        $years = array();
        $year = date("Y");
        for($i=0;$i<15;$i++){
            $years[$year+$i] = $year+$i;
        }
        $this->set("years",$years);
    }

    public function submitPayment()
    {
        // Alert for debugging purposes only
        // Log::addEntry("Start with submitPayment", t('Community Store Square'));
        $customer = new StoreCustomer();
        $currency = Config::get('community_store_square.currency');
        $mode =  Config::get('community_store_square.mode');
		
		$client = null;
        if ($mode == 'sandbox') {
            $privateKey = Config::get('community_store_square.sandboxAccessToken');
            $locationKey = Config::get('community_store_square.sandboxLocation');
			$client = new SquareClient([
				'accessToken' => $privateKey,
				'environment' => Environment::SANDBOX,
			]);
        } else {
            $privateKey = Config::get('community_store_square.liveAccessToken');
            $locationKey = Config::get('community_store_square.liveLocation');
			$client = new SquareClient([
				'accessToken' => $privateKey,
				'environment' => Environment::PRODUCTION,
			]);
        }
		
		$paymentsApi = $client->getPaymentsApi();
		$body_sourceId = $_POST['nonce'];
		$body_idempotencyKey = uniqid();
		$body_amountMoney = new Models\Money();
		$body_amountMoney->setAmount(StoreCalculator::getGrandTotal()*100);
		$body_amountMoney->setCurrency($currency);
		$body = new Models\CreatePaymentRequest(
			$body_sourceId,
			$body_idempotencyKey,
			$body_amountMoney
		);
		$body->setAutocomplete(true);
		$apiResponse = $paymentsApi->createPayment($body);

		if ($apiResponse->isSuccess()) {
			$createPaymentResponse = $apiResponse->getResult();
			return array('error'=>0, 'transactionReference'=>$createPaymentResponse->getPayment()->getId());
		} else {
			return array ('error'=>1,'errorMessage'=>$apiResponse->getErrors());
		}
    }

    public function getPaymentMethodName(){
        return 'Square';
    }

    public function getPaymentMethodDisplayName()
    {
        return $this->getPaymentMethodName();
    }

    public function getName()
    {
        return $this->getPaymentMethodName();
    }

}

return __NAMESPACE__;
