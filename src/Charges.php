<?php 
namespace app\components\tap;

// use app\models\LogsAll;
use Yii;
use TapPayments\GoSell as GoSellSdk;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

class Charges extends \yii\base\Component
{
	const CURRENCY_KWD             = 'KWD';
	const PHONE_CODE_KUWAIT        = 965;
	const PAYMENT_PAGE_BASE_URL    = 'https://checkout.payments.tap.company/?mode=page';
	const SUCCESS_CHARGE_INITIATED = 'INITIATED';
	const SUCCESS_CHARGE_CAPTURED  = 'CAPTURED';
	const PAYMENT_METHOD_KNET      = 'src_kw.knet';
	const PAYMENT_METHOD_CARD      = 'src_card';
  const PAYMENT_METHOD_ALL       = 'src_all';
	public $auth                   = false;
	private $_errorsMessage        = 'Payment Error';
	private $_data;
	private $_response;
  public $urlRedirect;
  public $urlPost;



  public function init()
  {
  	parent::init();
  	GoSellSdk::setPrivateKey(Yii::$app->params['tap_config']['key_screet']);
  }

  public function getErrorsMessage(){
  	return $this->_errorsMessage;
  }

  protected function setChargesData($data){
  	$this->_data = [
			'amount'       => $data['order']['amount'],
			'currency'     => $data['order']['currency'],
			'threeDSecure' => true,
			'save_card'    => false,
			'description'  => $data['order']['description'],
			'metadata'     => isset($data['meta_data']) ? $data['meta_data'] : [],
		  'reference'=> [
				'transaction' => $data['order']['payment_reference'],
				'order'       => $data['order']['reference']
		  ],
		  'receipt'=> [
				'email' => false,
				'sms'   => true
		  ],
		  'customer'=> $data['customer'],
		  'source'=> [
		    'id'=> $data['payment_method'],
		  ],
		  'post'=> [
		    'url'=> $this->urlPost,
		  ],
		  'redirect'=> [
		    'url'=> $this->urlRedirect,
		  ]
		];
  }

  protected function setChargesResponse($response){
  	if (isset($response->status) && ($response->status == $this::SUCCESS_CHARGE_INITIATED || $response->status == $this::SUCCESS_CHARGE_CAPTURED)) {
			$responseMap = ArrayHelper::toArray($response);
  	}else{
  		if (isset($response->errors) && is_array($response->errors)) {
  			foreach ($response->errors as $error) {
  				if (isset($error->description)) {
  					$this->_errorsMessage .= is_array($error->description) ? implode(', ',$error->description) : ', '.$error->description;
  				}
  			}
  		}
  		$this->_response = false;
  		return $this->_response;
  	}
  	$this->_response = $responseMap;
  }

  public function getData(){
  	return $this->_data;
  }

  public function charges($data){
  	$this->setChargesData($data);
  	$this->setChargesResponse(GoSellSdk\Charges::create($this->_data));
  	return $this->_response;
  }

  public function getDetail($transactionId){
  	$this->_data = ArrayHelper::toArray(GoSellSdk\Charges::retrieve($transactionId));
  	return $this->_data;
  }

  public function getIsIntiated():bool
  {
    return $this->getStatusName() == $this::SUCCESS_CHARGE_INITIATED;
  }

  public function getIsCaptured():bool
  {
    return $this->getStatusName() == $this::SUCCESS_CHARGE_CAPTURED;
  }

  public function getStatusName():string
  {
    return isset($this->data['status']) ? $this->data['status'] : NULL;
  }
}