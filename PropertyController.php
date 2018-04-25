<?php
namespace App\Controller;
use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Mailer\Email;
use Cake\Routing\Router;
require_once(ROOT.DS.'vendor'. DS.'eWAY-RapidAPI-php-master'.DS.'lib'.DS.'eWAY'. DS.'RapidAPI.php');		
use eWAY;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha512;

/**
 * Sites Controller is for manage all the cms pages and and home page of the site. eWAY-RapidAPI-php-master/lib/eWAY
 *
 * @property \Admin\Model\Table\Cms $Cms
 */
class PropertyController extends AppController
{
	public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->loadComponent('Email');
        $this->Auth->allow([]);
    }
    
    /**
     * [index function is for home page of the site]
     * 
     */
    public function addProperty()
    {
		$categoryName = TableRegistry::get('Category');
		$property = TableRegistry::get('Property');
		$property_service = TableRegistry::get('PropertyService');
		$loggedIn = $this->Auth->user();
		if ($this->request->is('post')) {
			
			/*************************************************************************************/
			foreach($this->request->data['service'] as $service_value){
				if($service_value['id']!=5){
					$categoryvalues = $categoryName->find('all',array('conditions'=>array('status' =>"A",'id'=>$service_value['id']) ,'limit'=>6 ,'fields'=>array('ubookr_id','id')))->first();
					//echo $categoryvalues['ubookr_id']; die;
					$signer = new Sha512();
					$token = (new Builder())->setIssuedAt(time()) // Configures the time that the token was issue (iat claim)
										   ->setExpiration(time() + 3600) // Configures the expiration time of the token (exp claim)
										   ->set('user_id', '27605') // Configures a new claim, called "user_id" with YOUR user_id (as a string)
										   ->sign($signer, 'pSGjWhbAhAgyuB96eBa1raPOZPM6') // creates a signature using YOUR secret
										   ->getToken(); // Retrieves the generated token
										   
					// Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
					$ch = curl_init();
					$url = "https://ubookr.com/api/services?";
					$serviceType=	"serviceType=".$categoryvalues['ubookr_id'];
					$postcode=	"&postcode=".$this->request->data['zip'];
					$url_crl  = $url . $serviceType . $postcode;
					//echo $url_crl; die;
					curl_setopt($ch, CURLOPT_URL, $url_crl);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
					$headers = array();
					$headers[] =  "Ubookr-Jws: ".$token;
					$headers[] = "Accept: application/json";
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
					$result = curl_exec($ch);
					if (curl_errno($ch)) {
						echo 'Error:' . curl_error($ch);
					}
					curl_close ($ch);
					$main_data = json_decode($result);
					if((empty($main_data)) || (empty($main_data->items))){
						$this->Flash->error(__('You have not available service in this zip code'));
						return $this->redirect(['controller' => '/','action' =>'add-property']);
					}
				}
			}
			/*************************************************************************************/
			$this->request->data['user_id']=$loggedIn['id'];
			$newProperty = $property->newEntity();
			$this->request->data['countryid']=$this->request->data['countryid'];
			$this->request->data['state']=$this->request->data['state_id'];
			$this->request->data['suburb']=$this->request->data['city_id'];
			$this->request->data['pin']=$this->request->data['zip'];
			$property_to_insert = $property->patchEntity($newProperty, $this->request->data);
			$result = $property->save($property_to_insert);
			foreach($this->request->data['service'] as $service_value){
				foreach($service_value['service_file'] as $service_multiple){
				$this->request->data['property_id'] = $result['id'];
				$this->request->data['service_id'] = $service_value['id'];
				$this->request->data['service_cost_id'] = $service_multiple['service_cost_id'];
				$property_services = $property_service->newEntity();
				$propertyServices = $property_service->patchEntity($property_services, $this->request->data);
				$property_service->save($propertyServices);
			}
			}
			$settings = $this->getSiteSettings();
			$this->loadModel('Users');
			$user_details=$this->Users->find('all',['conditions'=>['id'=>$loggedIn['id']]])->first();
			$this->loadComponent('Email');
			$property_details=$property->find('all',['conditions'=>['id'=>$result['id']]])->first();
			$property_service_details=$property_service->find('all',['conditions'=>['property_id'=>$result['id']],'contain'=>['ServiceCosts']])->toArray();
			
			//if($this->Email->postProperty($settings,$user_details,$property_details,$property_service_details)){
				//$this->Email->postPropertyadmin($settings,$user_details,$property_details,$property_service_details);
				$this->Flash->success(__('You have successfully posted  a property'));
				return $this->redirect(['controller' => '/','action' =>'add-property']);
			//}
		}
		
		$categoryMatchValues = $categoryName->find('all',array('conditions'=>array('status' =>"A") ,'limit'=>6 ,'fields'=>array('cat_name','id')))->toArray();
		$service_cost = TableRegistry::get('ServiceCosts');
		//$service_vedio_match = $service_cost->find('all',array('conditions'=>array('status' =>"A" ,'service_id'=>1),'order'=>array('ServiceCosts.display_order'=>'ASC')))->toArray();
		$service_vedio_match=$service_cost->find('all',['conditions'=>['ServiceCosts.status'=>'A','ServiceCosts.service_id'=>1],'order'=>['ServiceCosts.display_order'=>'ASC']])->toArray();
		$color_floor_plan = $service_cost->find('all',array('conditions'=>array('status' =>"A" ,'service_id'=>2,'floor_plan_type'=>'C'),'order'=>array('ServiceCosts.display_order'=>'ASC')))->toArray();
		$black_white_floor_plan=$service_cost->find('all',array('conditions'=>array('status' =>"A" ,'service_id'=>2,'floor_plan_type'=>'B'),'order'=>array('ServiceCosts.display_order'=>'ASC')))->toArray();
		$service_virtual_match=$service_cost->find('all',['conditions'=>['ServiceCosts.status'=>'A','ServiceCosts.service_id'=>3],'order'=>['ServiceCosts.display_order'=>'ASC']])->toArray();
		$service_photography_match = $service_cost->find('all',array('conditions'=>array('status' =>"A" ,'service_id'=>4),'order'=>array('ServiceCosts.display_order'=>'ASC')))->toArray();
		$service_sign_match = $service_cost->find('all',array('conditions'=>array('status' =>"A" ,'service_id'=>5),'order'=>array('ServiceCosts.display_order'=>'ASC')))->toArray();
		//pr($service_sign_match);die;
		$service_sign_match_first = $service_cost->find('all',array('conditions'=>array('status' =>"A" ,'service_id'=>5),'order'=>array('ServiceCosts.display_order'=>'ASC')))->first();
		//pr($service_sign_match_first);die;
		$service_drone_match = $service_cost->find('all',array('conditions'=>array('status' =>"A" ,'service_id'=>6),'order'=>array('ServiceCosts.display_order'=>'ASC')))->toArray();
		$country = TableRegistry::get('Country');
		$countries = $country->find('list',array('keyFields'=>'id','valueField'=>'name'))->toArray();
		$this->set(compact('service_sign_match_first','black_white_floor_plan','color_floor_plan','countries','categoryMatchValues','service_vedio_match','service_virtual_match','service_photography_match','service_sign_match','service_drone_match'));
		
    }
	
	
	
	/***************************************************Payment  Gatway*************************************************************************/
	
	public function paymentProcess($id = null)
    {
		$user = TableRegistry::get('Users');
		$property = TableRegistry::get('Property');
		$transaction = TableRegistry::get('Transaction');
		$loggedIn = $this->Auth->user();
		$ids=0;
		if($id==""){
			$this->redirect(array('controller'=>'/','action'=>'index'));
		}
		else{
			$ids=base64_decode($id);
		}
		$user_details=$user->find('all',['conditions'=>['id'=>$loggedIn['id']]])->first();
		$property_details=$property->find('all',['conditions'=>['id'=>$ids]])->first();
		$in_page = 'before_submit';
		if ($this->request->is('post')) {
		//pr($this->request->data); die;	
		$request = new eWAY\CreateDirectPaymentRequest();
		$request->Customer->Reference = $this->request->data['txtCustomerRef'];
		//$request->Customer->Title = $this->request->data['ddlTitle'];
		$request->Customer->FirstName = $this->request->data['txtFirstName'];
		$request->Customer->LastName = $this->request->data['txtLastName'];
		$request->Customer->CompanyName = $this->request->data['txtCompanyName'];
		$request->Customer->JobDescription = $this->request->data['txtJobDescription'];
		$request->Customer->Street1 = $this->request->data['txtStreet'];
		$request->Customer->City = $this->request->data['txtCity'];
		$request->Customer->State = $this->request->data['txtState'];
		$request->Customer->PostalCode = $this->request->data['txtPostalcode'];
		$request->Customer->Country = $this->request->data['txtCountry'];
		$request->Customer->Email = $this->request->data['txtEmail'];
		$request->Customer->Phone = $this->request->data['txtPhone'];
		//$request->Customer->Mobile = $this->request->data['txtMobile'];
		//$request->Customer->Comments = $this->request->data['txtComments'];
		//$request->Customer->Fax = $this->request->data['txtFax'];
		//$request->Customer->Url = $this->request->data['txtUrl'];

		$request->Customer->CardDetails->Name = $this->request->data['txtCardName'];
		$request->Customer->CardDetails->Number = $this->request->data['txtCardNumber'];
		$request->Customer->CardDetails->ExpiryMonth = $this->request->data['ddlCardExpiryMonth'];
		$request->Customer->CardDetails->ExpiryYear = $this->request->data['ddlCardExpiryYear'];
		//$request->Customer->CardDetails->StartMonth = $this->request->data['ddlStartMonth'];
		//$request->Customer->CardDetails->StartYear = $this->request->data['ddlStartYear'];
		$request->Customer->CardDetails->IssueNumber = $this->request->data['txtIssueNumber'];
		$request->Customer->CardDetails->CVN = $this->request->data['txtCVN'];

    // Populate values for ShippingAddress Object.
    // This values can be taken from a Form POST as well. Now is just some dummy data.
		$request->ShippingAddress->FirstName = "John";
		$request->ShippingAddress->LastName = "Doe";
		$request->ShippingAddress->Street1 = "9/10 St Andrew";
		$request->ShippingAddress->Street2 = " Square";
		$request->ShippingAddress->City = "Edinburgh";
		$request->ShippingAddress->State = "";
		$request->ShippingAddress->Country = "gb";
		$request->ShippingAddress->PostalCode = "EH2 2AF";
		$request->ShippingAddress->Email = "your@email.com";
		$request->ShippingAddress->Phone = "0131 208 0321";
		// ShippingMethod, e.g. "LowCost", "International", "Military". Check the spec for available values.
		$request->ShippingAddress->ShippingMethod = "LowCost";

    if ($this->request->data['ddlMethod'] == 'ProcessPayment' || $this->request->data['ddlMethod'] == 'Authorise' || $this->request->data['ddlMethod'] == 'TokenPayment') {
        // Populate values for LineItems
        $item1 = new eWAY\LineItem();
        $item1->SKU = "SKU1";
        $item1->Description = "Description1";
        $item2 = new eWAY\LineItem();
        $item2->SKU = "SKU2";
        $item2->Description = "Description2";
        $request->Items->LineItem[0] = $item1;
        $request->Items->LineItem[1] = $item2;

        // Populate values for Payment Object
        $request->Payment->TotalAmount = $this->request->data['txtAmount'];
        $request->Payment->InvoiceNumber = $this->request->data['txtInvoiceNumber'];
        $request->Payment->InvoiceDescription = $this->request->data['txtInvoiceDescription'];
        $request->Payment->InvoiceReference = $this->request->data['txtInvoiceReference'];
        $request->Payment->CurrencyCode = $this->request->data['txtCurrencyCode'];
    }
    $request->Method = $this->request->data['ddlMethod'];
    $request->TransactionType = $this->request->data['ddlTransactionType'];
		
		
		
		
		
		$eway_params = array();
		if ($this->request->data['ddlSandbox']) {
		$eway_params['sandbox'] = true;
		}
		$service = new eWAY\RapidAPI(EWAY_APIKEY, EWAY_PASSWORD, $eway_params);
		$result = $service->DirectPayment($request);
		//pr($result); die;
		if (isset($result->Errors)) {
				// Get Error Messages from Error Code.
				$ErrorArray = explode(",", $result->Errors);
				$lblError = "";
					foreach ( $ErrorArray as $error ) {
						$error = $service->getMessage($error);
						$lblError .= $error . "<br />\n";;
					}
			} 
			else {
				$query = $user->query();
				$query->update()
					->set(['card_holer_name' => $this->request->data['txtCardName'],'card_number'=>$this->request->data['txtCardNumber'],'exp_date'=>($this->request->data['ddlCardExpiryMonth'].' / '.$this->request->data['ddlCardExpiryYear'])])
					->where(['id' => $loggedIn['id']])
					->execute();
				$query = $property->query();
				$query->update()
					->set(['payment_status'=>"Y"])
					->where(['id' => $this->request->data['txtPropHid']])
					->execute();
				/************************/
				$data['amount']=($this->request->data['txtAmount'] / 100 );
				$data['user_id']=$this->request->data['txtTokenCustomerID'];
				$data['property_id']=$this->request->data['txtPropHid'];
				$data['transaction_id']=$result->TransactionID;
				$data['transaction_date_time']=date('Y-m-d h:i:s');
				$transaction_data = $transaction->newEntity();
				$transactionServices = $transaction->patchEntity($transaction_data, $data);
				$transaction->save($transactionServices);
				/***********************/
				
			}
			//$this->Flash->success(__('You have successfully payment'));
			//$this->redirect(array('controller'=>'project','action'=>'realstateMyFile'));
		}
		//pr($property_details); die;
		$this->set(compact('in_page','service','user_details','property_details'));
	
	}
	
	
	
	
}
