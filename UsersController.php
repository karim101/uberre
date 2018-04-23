<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Mailer\Email;
use Cake\Routing\Router;
require_once(ROOT . DS . 'vendor' . DS . "verot/class.upload.php/src" . DS . "class.upload.php");

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha512;

//require_once(ROOT.DS.'vendor'. DS.'php-jwt-master'.DS.'src'. DS.'JWT.php');
/**
 * Users Controller is for manage all the user registration login forgot pass, change password etc...
 *
 * @property \Admin\Model\Table\Cms $Cms
 */
class UsersController extends AppController
{
	public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->loadComponent('Email');
        $this->Cookie->configKey('User', [
            'expires' => '+10 days',
            'httpOnly' => true
        ]);
        $this->Auth->allow(['login','signUpRealEstate','resetPassword','forgotPassword','varify','ajxGetState','ajxGetCity','signUpServiceProvider','surveyPage','ajxGetZip']);
    }
    public function index()
    {
    	//return $this->redirect(Router::url('/users/login', true));
    }
   /*-- Start registration function --*/	
	public function signUpRealEstate()
    {
		$ref_url = Router::url( '/', true );
		$loggedIn = $this->Auth->user();
		if(!empty($loggedIn)){
			return $this->redirect($ref_url);
		}
		if($this->request->data){
			$this->request->data['signup_ip']=$_SERVER['REMOTE_ADDR'];
			$this->request->data['login_type']="N";
			$this->request->data['type']="R";
			$this->request->data['signup_string'] = $this->generateRandomString(3).time().$this->generateRandomString(3);
			$newUsers = $this->Users->newEntity();
			$data_to_insert = $this->Users->patchEntity($newUsers, $this->request->data);
			if($data_to_insert ->errors('email')) {
				$this->Flash->error(__('Email id already exist, try another.'));
			}
			if($data_to_insert ->errors('username')) {
				$this->Flash->error(__('Username already exist, try another.'));
			}
			if($this->Users->save($data_to_insert)){
				$url = Router::url('/', true).'users/varify/'.$this->request->data['signup_string'].'/'.base64_encode(time());
				$settings = $this->getSiteSettings();
				if($this->Email->userRegister($this->request->data['email'], $url, $this->request->data, $settings)){
					$this->Flash->success(__('You are successfully registered with us. Please check your email for activation link.'));
					return $this->redirect(['controller' => 'users', 'action' => 'login']);
				}
			}
		}
		$country = TableRegistry::get('Country');
		$countries = $country->find('list',array('keyFields'=>'id','valueField'=>'name'))->toArray();
		 $this->set(compact('countries'));
	}
	/*-- End registration function --*/	
	/*--Service Provider registration-------*/
	public function signUpServiceProvider()
    {
		$ref_url = Router::url( '/', true );
		$loggedIn = $this->Auth->user();
		if(!empty($loggedIn)){
			return $this->redirect($ref_url);
		}
		if($this->request->data){
			//pr($this->request->data); die;
			$this->request->data['signup_ip']=$_SERVER['REMOTE_ADDR'];
			$this->request->data['login_type']="N";
			$this->request->data['type']="S";
			$this->request->data['signup_string'] = $this->generateRandomString(3).time().$this->generateRandomString(3);
			$newUsers = $this->Users->newEntity();
			$data_to_insert = $this->Users->patchEntity($newUsers, $this->request->data);
			if($data_to_insert ->errors('email')) {
				$this->Flash->error(__('Email id already exist, try another.'));
			}
			if($data_to_insert ->errors('username')) {
				$this->Flash->error(__('Username already exist, try another.'));
			}
			
			$result = $this->Users->save($data_to_insert);
			$member =$result['id'];
			if($member!=""){			
			if(!empty($this->request->data['service'])){
				foreach($this->request->data['service'] as $service_value){
					foreach($service_value['service_file'] as $service_multiple_image){
						if(array_key_exists('file', $service_multiple_image) ){
							if( isset($service_multiple_image['file']['name'])) {
								if($service_multiple_image['file']['name']!=''){
										$original_file_name = $service_multiple_image['file']['name'];
										$file_name =date('ymdHis').'-'.rand(10000, 9999).'-'.$service_multiple_image['file']['name'];
										move_uploaded_file($service_multiple_image['file']['tmp_name'], WWW_ROOT.'uploads/service_image/'.$file_name);
										copy(WWW_ROOT.'/uploads/service_image/'.$file_name, WWW_ROOT.'uploads/service_image/thumb/'.$file_name);
										$this->request->data['file'] = $file_name;
									}
							}	else {
								$this->request->data['file'] =$service_multiple_image['file'];
							}
							if(!empty($service_value['id'])){
								$this->loadModel('ApplyCategory');
								$this->request->data['user_id'] = $member;
								$this->request->data['category_id'] = $service_value['id'];
								$ApplyCategory = $this->ApplyCategory->newEntity();
								$ApplyCategory = $this->ApplyCategory->patchEntity($ApplyCategory, $this->request->data);
								$this->ApplyCategory->save($ApplyCategory);
							}	
							
						}
					}			
				}
				$url = Router::url('/', true).'users/varify/'.$this->request->data['signup_string'].'/'.base64_encode(time());
				$settings = $this->getSiteSettings();
				if($this->Email->userRegister($this->request->data['email'], $url, $this->request->data, $settings)){
					$this->Flash->success(__('You are successfully registered with us. Please check your email for activation link.'));
					return $this->redirect(['controller' => 'users', 'action' => 'login']);
				}
			}
		}
		}
		$country = TableRegistry::get('Country');
		$countries = $country->find('list',array('keyFields'=>'id','valueField'=>'name'))->toArray();
		$categoryName = TableRegistry::get('Category');
		$categoryMatchValues = $categoryName->find('all',array('conditions'=>array('status' =>"A") ,'limit'=>6 ,'fields'=>array('cat_name','id')))->toArray();
		$this->set(compact('countries','categoryMatchValues'));
	}
	/*--End Service Provider registration---*/
	/*------------------get State------------------------------------------*/
	public function ajxGetState() {
		$this->viewBuilder()->layout('ajax');
		$country_id = $_REQUEST['getId'];
		$this->loadModel('Admin.State');
		$states = $this->State->find('list',['keyField' => 'id','valueField' => 'name'])->where(['State.country_id'=>$country_id,'State.status'=>"A"])->toArray();
		$this->set(compact('states'));
	}
	/*------------------get City------------------------------------------*/
	public function ajxGetCity() {
		$this->viewBuilder()->layout('ajax');
		$state_id = $_REQUEST['getId'];
		$this->loadModel('Admin.City');
		$citys = $this->City->find('list',['keyField' => 'id','valueField' => 'name'])->where(['City.state_id'=>$state_id])->toArray();
		$this->set(compact('citys'));
	}
	/*-- Start  varify mail for registration  --*/
	/**--------------------Zip Code--------------------------*/
	public function ajxGetZip() {
		$this->viewBuilder()->layout('ajax');
		$city_id = $_REQUEST['getId'];
		$this->loadModel('Admin.City');
		$zip = $this->City->find('all',['conditions'=>['id' =>$city_id]])->first();
		//pr($zip); die;
		$this->set(compact('zip'));
	}
	
	
	
	public function varify($signupString=null, $signupTime=null)
	{
    	$session  = $this->request->session();
        $signupTime = base64_decode($signupTime);
        $user = TableRegistry::get('Users');
        if($this->isDataExist('Users', 'signup_string', $signupString)) {
            $duration = time()-$signupTime;
            if($duration>LINK_TIME){
                $this->Flash->error(__('URL has expired, please contact with the site Owner.'));
                $session->write('from_verify', 'yes');
				return $this->redirect(['controller' => 'users', 'action' => 'login']);
            }else{
                $query = $user->query();
				$verifyuser = $user->find('all',['conditions'=>['signup_string' =>$signupString]])->first();
                $query->update()
                    ->set(['status' => 'A', 'signup_string'=>''])
                    ->where(['signup_string' => $signupString])
                    ->execute();
				$settings = $this->getSiteSettings();
				$this->Email->adminVarifiedRegister($verifyuser, $settings);
				$this->Flash->success(__('Your Email id has been successfully verified'));
				$session->write('from_verify', 'yes');
				return $this->redirect(['controller' => 'users', 'action' => 'login']);
            }
        }else{
            $this->Flash->error(__('Invalid URL, please contact with the site Owner.'));
            $session->write('from_verify', 'yes');
			return $this->redirect(['controller' => 'users', 'action' => 'login']);
        }
    }
	/*-- end varify mail for registration  --*/
	/*-- Start Login function --*/
	public function login( $parms=null )
    {
		$ref_url = Router::url( '/', true );
		$loggedIn = $this->Auth->user();
		if(!empty($loggedIn)){
			return $this->redirect($ref_url);
		}
		$User = TableRegistry::get('Users');
		$reference_service_providers = TableRegistry::get('ReferenceServiceProvider');
		if ($this->request->is('post')) {
			$session = $this->request->session();
			$user_data = $User->find('all', ['fields' => ['email'], 'conditions' => ['username' => $this->request->data['email']]])->first();
			if(!empty($user_data)){
				$this->request->data['email'] = $user_data['email'];
			}
			if(empty($this->Auth->user())){
                $user = $this->Auth->identify();
				if ($user) {
					if($user['status']=='I'){
						$this->Flash->error(__('This account is deactivated'));
					}else{
							if($user['login_type']=='N'){
								if($user['type']=='R'){
									$this->Auth->setUser($user);
									return $this->redirect($ref_url);
								}else{
									$user_ref_data = $reference_service_providers->find('all', ['conditions' => ['user_id' => $user['id']]])->count();
									if($user_ref_data == 0){
										$this->Auth->setUser($user);
										return $this->redirect(['controller' => 'users', 'action' => 'signUpServiceProviderStep2']);
									}
									else if($user['public_liability_insurence']=="" && $user['policy_number']=="" && $user['amount_insured']==0){
										$this->Auth->setUser($user);
										return $this->redirect(['controller' => 'users', 'action' => 'signUpServiceProviderStep3']);
									}
									else{
										$this->Auth->setUser($user);
										return $this->redirect($ref_url);
									}
								}							
							}else{
								$this->Flash->error(__('login failed, please try again'));
							}
						}
				}
				else{
					$this->Flash->error(__('This email or password not match, please try again'));
				}
			}
		}
		$this->set(compact('parms'));
	}
	/*-- End  Login function --*/
	/*-- start Logout function --*/
	public function logout()
	{
         $session = $this->request->session();
		 $this->request->session()->delete('user_type');
		 $this->request->session()->destroy();
         $this->Auth->logout();
         $session->write('User.logout', 1);
         return $this->redirect(Router::url('/', true));
    }
	/*-- End Logout function --*/
	/*--start randam string generate function --*/
	function random_string($length)
	{
	    return substr(str_repeat(md5(rand()), ceil($length/32)), 0, $length);
	}
	/*--End randam string generate function --*/
	/*-- Start Reset your Password --*/
	public function resetPassword()
    {
		 if ($this->request->is('post')) {
			$this->loadModel('Users');
			//$verifyEmail = $this->Users->find()->where(['email' => $this->request->data['email']])->first();
			 $verifyEmail = $this->Users->find('all',['conditions'=>['or' => ['email' =>$this->request->data['email'],'username' => $this->request->data['email']]]])->first();
			if(isset($verifyEmail) && count($verifyEmail) > 0 ) {
				$i_id = $verifyEmail->id;
				$getData = $this->Users->get($i_id);
				$resetStr = $this->random_string(20);
			}
			else{
				$this->Flash->error(__('Please provide registered email-id to reset password'));
				return $this->redirect(['controller' => 'users', 'action' => 'reset-password']);
			}
			$patch_entity_qry = $this->Users->patchEntity($getData,['forget_password_string' => $resetStr]);
			if($this->Users->save($patch_entity_qry) ) {
				$url = Router::url('/', true);
				$link = Router::url('/users/forgotPassword?token='.$verifyEmail->id.'&str='.$resetStr.'&time='.base64_encode(time()), true);
				$to = $verifyEmail->email;
				$subject = 'Uber For Real Estate Forgot Password Verification';
				$full_name = $verifyEmail->first_name;
				$settings = $this->getSiteSettings();
				$data = array('fullname'=>$full_name,'username'=>$verifyEmail->username,'link'=>$link,'url'=>$url,'site_name'=>WEBSITE_NAME);
				$this->Email->resetPassword($to,$subject,$data,$settings);
			}
			$this->Flash->success(__('Please check your email for password reset link'));
		}	 
		//echo substr(number_format(time() * rand(),0,'',''),0,6);
	}
	/*-- End Reset your Password --*/
	/* -- Start Forgot Your Password --*/
	public function forgotPassword()
    {
		$uid_string = isset($_GET['token'])?$_GET['token']:''; 
		$mid_string = isset($_GET['str'])?$_GET['str']:'';
		$for_time = isset($_GET['time'])?$_GET['time']:'';
		$duration = time()-base64_decode($for_time);
		if($duration>LINK_TIME){
			$this->Flash->error(__('URL has expired, please contact with the site Owner.'));
			$this->redirect(array('controller'=>'users','action'=>'reset-password'));
        }
		else{
		$verifyUser = $this->Users->find()->where(['id' => $uid_string,'forget_password_string'=>$mid_string,'login_type'=>"N",'status'=>"A" ])->count();
		if($verifyUser != 0 ) {
			if ($this->request->is(['post'])) {
				$uid = $this->request->data['token'];
				$rstr = $this->request->data['mid_string'];
				if($this->request->data['password'] == $this->request->data['confirm_password']){
					if( $uid != '' && $rstr != '' ) {
						$id = $uid;
						$verifyUser = $this->Users->find()->where(['id' => $id,'forget_password_string'=>$rstr ])->count();
						if(count($verifyUser) > 0 ) {
							$UserTable = TableRegistry::get('Users');
							$userData = $UserTable->get($id);
							$password = $this->request->data['password'];
							$patch_entity_qry = $UserTable->patchEntity($userData,['password' => $password]);
							$patch_entity_qry = $UserTable->patchEntity($userData,['forget_password_string' =>""]);
							if ($UserTable->save($patch_entity_qry)) {
								$this->Flash->success(__('Your password has been updated successfully.'));
								$this->redirect(array('controller'=>'users','action'=>'login'));
							} else {
								$this->Flash->error(__('Your profile was not updated'));
							}
						} else {
							$this->Flash->error(__('this member not exists.'));
						}
					} else {
						$this->Flash->error(__('There were some problems.please try again later.'));
					}
				}else{
					$this->Flash->error(__('Password does not match.please try again later.'));
				}
			}
		}else{
			$this->Flash->error(__('This link has been expired'));
			return $this->redirect(['controller' => 'users', 'action' => 'reset-password']);
		}
		}
		$this->set(compact('uid_string','mid_string'));
		
	}
	/* -- End Forgot Your Password --*/
	/*-- start Change password --*/
	public function changePassword()
    {
		$loggedIn = $this->Auth->user();
		$user_details = $this->Users->get($loggedIn['id'],[
				'contain'=>[]
			]);
			if ($this->request->is(['patch', 'post', 'put'])) {
				$this->request->data['password'] = $this->request->data['new_password'];
				$password = $this->Users->patchEntity($user_details, $this->request->data, ['validate' => 'password']);
				if ($this->Users->save($password)) {
					$this->Flash->success(__('Your password has been successfully updated.'));
					return $this->redirect(Router::url('/users/change-password/', true));
				} else {
					$this->Flash->error(__('Password does not match, please enter your current password properly.'));
					return $this->redirect(Router::url('/users/change-password/', true));
				}
			}
		$this->set(compact('user_details')); 
    }
	/*-- End Change password --*/
	/*-- start Service Provider Step2 registration --*/
	public function signUpServiceProviderStep2()
    {
		$loggedIn = $this->Auth->user();
		if($this->request->data){
			//pr($this->request->data); die;
			if(!empty($this->request->data['user'])){
				foreach($this->request->data['user'] as $value){
					$this->request->data['company_name']=$value['company_name'];
					$this->request->data['first_name']=$value['first_name'];
					$this->request->data['last_name']=$value['last_name'];
					$this->request->data['phone_no']=$value['phone_no'];
					$this->request->data['email']=$value['email'];
					$this->request->data['created']=date('Y-m-d h:i:s');
					$this->request->data['user_id']=$loggedIn['id'];
					$reference_service_providers = TableRegistry::get('ReferenceServiceProvider');
					$newUsers = $reference_service_providers->newEntity();
					$data_to_insert = $reference_service_providers->patchEntity($newUsers, $this->request->data);
					$result=$reference_service_providers->save($data_to_insert);
					$member =$result['id'];
					$settings = $this->getSiteSettings();
					$all_ref = $this->Users->find()->where(['id' => $loggedIn['id']])->first();
					$url = Router::url('/', true).'users/survey-page/'.base64_encode($member);
					$this->Email->multipleService($this->request->data['email'],$url,$all_ref,$this->request->data, $settings);
				}
				return $this->redirect(['controller' => 'users', 'action' => 'signUpServiceProviderStep3']);
			}else{
				return $this->redirect(['controller' => 'users', 'action' => 'signUpServiceProviderStep2']);
			}
		}
    }
	/*-- start Service Provider Step3 registration --*/
	public function signUpServiceProviderStep3()
    {
		$ref_url = Router::url( '/', true );
		$loggedIn = $this->Auth->user();
		if($this->request->data){
			$user = TableRegistry::get('Users');
			$query = $user->query();
					$query->update()
							->set(['public_liability_insurence' => $this->request->data['public_liability_insurence'], 'policy_number'=>$this->request->data['policy_number'],'amount_insured'=>$this->request->data['amount_insured']])
							->where(['id' => $loggedIn['id']])
							->execute();
			$this->Flash->success(__('Thank you for completing your application to become an Uber for Real Estate service provider'));
			return $this->redirect($ref_url);
		}
    }
	
	public function realstateEditProfile()
    {
		$loggedIn = $this->Auth->user();
		$this->loadModel('Users');
		$verifycustomer = $this->Users->find()->where(['id' => $loggedIn['id']])->first();
		if($this->request->data){
			$data_to_insert = $this->Users->patchEntity($verifycustomer, $this->request->data);
    			if($this->Users->save($data_to_insert)){
    				$this->Flash->success(__('Your account information has been updated.'));
    				return $this->redirect(Router::url('/users/realstate_edit_profile', true));
    			}
		}
		$country = TableRegistry::get('Country');
		$countries = $country->find('list',array('keyFields'=>'id','valueField'=>'name'))->toArray();
		$this->loadModel('Admin.State');
		$states = $this->State->find('list',['keyField' => 'id','valueField' => 'name'])->toArray();
		$this->loadModel('Admin.City');
		$citys = $this->City->find('list',['keyField' => 'id','valueField' => 'name'])->toArray();
		$this->set(compact('countries','verifycustomer','states','citys'));
    }
	public function realstateEditReference()
    {
		$loggedIn = $this->Auth->user();
		$reference_service_provider = TableRegistry::get('ReferenceServiceProvider');
		$all_ref = $reference_service_provider->find('all',['conditions'=>['user_id' => $loggedIn['id']]])->toArray();
		$this->set(compact('all_ref'));
		if($this->request->data){
			if(!empty($this->request->data['user'])){
				//pr($this->request->data);die;
				foreach($this->request->data['user'] as $value){
					$data['company_name']=$value['company_name'];
					$data['first_name']=$value['first_name'];
					$data['last_name']=$value['last_name'];
					$data['phone_no']=$value['phone_no'];
					$data['email']=$value['email'];
					$data['modified']=date('Y-m-d h:i:s');
					$data['user_id']=$loggedIn['id'];
					$data['id']=$value['hid'];
					$all_ref = $reference_service_provider->find('all',['conditions'=>['id' => $value['hid']]])->first();
					$data_to_insert = $reference_service_provider->patchEntity($all_ref, $data);
					$reference_service_provider->save($data_to_insert);
				}
				$this->Flash->success(__('Your account information has been updated.'));
				return $this->redirect(['controller' => 'users', 'action' => 'realstate-edit-reference']);
			}
		}
	}
	
	public function cardDetail()
    {
		$ref_url = Router::url( '/', true );
		$loggedIn = $this->Auth->user();
		if($this->request->data){
			//pr($this->request->data); die;
			$user = TableRegistry::get('Users');
			$query = $user->query();
					$query->update()
							->set(['card_holer_name' => $this->request->data['card_holer_name'], 'card_number'=>$this->request->data['card_number'],'exp_date'=>$this->request->data['exp_date']])
							->where(['id' => $loggedIn['id']])
							->execute();	
			$this->request->session()->write('card_number',$this->request->data['card_number']);
			return $this->redirect($ref_url);
		}
    }
	
	public function  surveyPage($referance_id=null)
    {
		$ref_url = Router::url( '/', true );
		$ref_id = base64_decode($referance_id);
		$categoryName = TableRegistry::get('Category');
		$categoryMatchValues = $categoryName->find('all',array('conditions'=>array('status' =>"A") ,'limit'=>6 ,'fields'=>array('cat_name','id')))->toArray();
		if($this->request->data){
			$this->request->data['created']=date('Y-m-d h:i:s');
			if(!empty($this->request->data['service'])){
					$category_id =  implode(",",$this->request->data['service']);
					$this->request->data['service_id']=$category_id;
			}
			$surveyManagement = TableRegistry::get('SurveyManagement');
			$newsurvay = $surveyManagement->newEntity();
			$data_to_insert = $surveyManagement->patchEntity($newsurvay, $this->request->data);
			$surveyManagement->save($data_to_insert);
			$settings = $this->getSiteSettings();
			$reference_service_provider = TableRegistry::get('ReferenceServiceProvider');
			$all_ref = $reference_service_provider->find('all',['conditions'=>['id' => $this->request->data['ref_service_provider_id']]])->first();
			$url = Router::url('/', true).'users/sign-up-real-estate/';
			$this->Email->multipleRefService($all_ref,$url,$this->request->data, $settings);
			return $this->redirect($ref_url);
		}
		$this->Flash->success(__('You are successfully survay registered with us. Please check your email.'));
		$this->set(compact('categoryMatchValues','ref_id'));
    }
	
	public function ubookerService($property_id=null,$getdateval=null)
    {
		$loggedIn = $this->Auth->user();
		$time_slot = array();
		if ($this->request->is('post')) {
			$this->loadModel('Property');
			$this->loadModel('PropertyService');
			$this->loadModel('City');
			$this->loadModel('State');
			$service_details=$this->PropertyService->find('all',['conditions'=>['PropertyService.id'=>$this->request->data['property_id']],'contain'=>['ServiceCosts','Category']])->first();
			$property_details=$this->Property->find('all',['conditions'=>['Property.id'=>$service_details['property_id']],'contain'=>['City']])->first();
			/***************************Time and Date Available***************************************/
			$signer = new Sha512();
			$token = (new Builder())->setIssuedAt(time()) // Configures the time that the token was issue (iat claim)
								   ->setExpiration(time() + 3600) // Configures the expiration time of the token (exp claim)
								   ->set('user_id', '27605') // Configures a new claim, called "user_id" with YOUR user_id (as a string)
								   ->sign($signer, 'pSGjWhbAhAgyuB96eBa1raPOZPM6') // creates a signature using YOUR secret
								   ->getToken(); // Retrieves the generated token
			// Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
			$ch = curl_init();
			$url = "https://ubookr.com/api/services?";
			$serviceType=	"serviceType=".$service_details['category']['ubookr_id'];
			$postcode=	"&postcode=".$property_details['pin'];
			$url_crl  = $url . $serviceType . $postcode;
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
		}
/*************************************End***************************************************/	
		$current_date=date('Y-m-d');
		$prop_service_id=$this->request->data['property_id'];
		$this->set(compact('time_slot','current_date','prop_service_id','token','property_details','result'));
	}
	
/************************************************Service Booking*********************************************************************/
	
	public function ubookerServiceBooking()
    {
		$loggedIn = $this->Auth->user();
		 $this->autoRender = false;
		$this->viewBuilder()->layout(false);
		if ($this->request->is('post')) {
			$this->loadModel('Property');
			$this->loadModel('PropertyService');
			$this->loadModel('City');
			$this->loadModel('State');
			$service_details=$this->PropertyService->find('all',['conditions'=>['PropertyService.id'=>$this->request->data['property_service_id']],'contain'=>['ServiceCosts']])->first();
			$property_details=$this->Property->find('all',['conditions'=>['Property.id'=>$service_details['property_id']],'contain'=>['City']])->first();
			//pr($property_details); die;
			$headers = [];
				$cookie_val="";
				$data_value=array (
					"email"=> "karim@techpourri.com",
					"password"=> "12345678"
				);
				$json_data = json_encode($data_value);
				// Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "https://ubookr.com/api/users/logIn");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_HEADERFUNCTION,
					function($curl, $header) use (&$headers)
					{
						$len = strlen($header);
						$header = explode(':', $header, 2);
						if (count($header) < 2) // ignore invalid headers
							return $len;

						$name = strtolower(trim($header[0]));
						if (!array_key_exists($name, $headers))
							$headers[$name] = [trim($header[1])];
						else
							$headers[$name][] = trim($header[1]);

						return $len;
					}
				);
				$headers = array();
				$headers[] = "Content-Type: application/json";
				$headers[] = "Accept: application/json";
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				$result = curl_exec($ch);
				if (curl_errno($ch)) {
					echo 'Error:' . curl_error($ch);
				}
				curl_close ($ch);
				if(isset($headers['set-cookie'])){
					foreach($headers['set-cookie'] as $cookie){
						if(strpos($cookie, 'PLAY') !== false){
							$play_session = explode(';', $cookie);
							 $cookie_val = $play_session[0];
							break;
						}
					}
				}
				//pr($cookie_val)	; die;
				$data_value=array (
				  'id' => 1,
				  'supplier' => $service_details['service_cost']['supplierId'],
				  'supplierStaff' => $service_details['service_cost']['stuffId'],
				 // 'start' => date('Y-m-d h:i'),
				  'start' => $this->request->data['date_format']." ".$this->request->data['time_slot_pick'],
				  'contact' => 
				  array (
					'name' => $loggedIn['first_name']." ".$loggedIn['last_name'],
					'address' => 
					array (
					  'id' => 0,
					  'line1' => $property_details['street_address'],
					  'line2' => '',
					  'postcode' =>"'".$property_details['pin']."'",
					  'suburb' =>$property_details['city']['name'],
					  'state' =>'NSW',
					  'country' => 'AU',
					),
					'mobilePhoneNumber' => $loggedIn['phone'],
					'officePhoneNumber' => '8529637410',
					'emailAddress' => $loggedIn['email'],
				  ),
				  'cost' => $service_details['service_cost']['cost'],
				  'duration' => $service_details['service_cost']['duration'],
				  'service' => $service_details['service_cost']['ubookr_service_id'],
				  'addOns' => 
				  array (
					0 =>3,
					1 => 4,
				  ),
				  'access' => 
				  array (
					'option' => 'KEY_SAFE',
				  ),
				  'keyNumber' => '58256',
				  'notes' => 'Ubookr',
				  'supplierNotes' => 'Ubookr',
				  'clientJobNumber' => 'Ubookr',
				);
			//pr($data_value); die;
			$json_data = json_encode($data_value);
			$ch1 = curl_init();
			curl_setopt( $ch1, CURLOPT_COOKIE, $cookie_val);
			curl_setopt($ch1, CURLOPT_URL, "https://ubookr.com/api/bookings");
			curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch1, CURLOPT_POSTFIELDS, $json_data);
			curl_setopt($ch1, CURLOPT_POST, 1);
			$headers = array();
			$headers[] = "Content-Type: application/json";
			$headers[] = "Accept: text/plain";
			curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);
			$result = curl_exec($ch1);
			//pr($result); die;
			if (curl_errno($ch1)) {
				echo 'Error:' . curl_error($ch1);
			}
			curl_close ($ch1);
			$main_data = json_decode($result); 
			//pr($main_data); die;
			$query = $this->PropertyService->query();
			$query->update()
				->set(['order_id' => $main_data->id])
				->where(['id' => $service_details['id']])
				->execute();
			//pr($main_data); die;
		echo json_encode(['data'=>$main_data]); die();
		}
	}
	
	
	


	
	
	
	public function ubookerServiceInsert()
    {
		$this->viewBuilder()->layout(false);
		$headers = [];
		$cookie_val="";
		$data_value=array (
			"email"=> "karim@techpourri.com",
			"password"=> "12345678"
		);
		$json_data = json_encode($data_value);
		// Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
		$chservice = curl_init();
		curl_setopt($chservice, CURLOPT_URL, "https://ubookr.com/api/users/logIn");
		curl_setopt($chservice, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($chservice, CURLOPT_POSTFIELDS, $json_data);
		curl_setopt($chservice, CURLOPT_POST, 1);
		curl_setopt($chservice, CURLOPT_HEADERFUNCTION,
			function($curl, $header) use (&$headers)
			{
				$len = strlen($header);
				$header = explode(':', $header, 2);
				if (count($header) < 2) // ignore invalid headers
					return $len;

				$name = strtolower(trim($header[0]));
				if (!array_key_exists($name, $headers))
					$headers[$name] = [trim($header[1])];
				else
					$headers[$name][] = trim($header[1]);

				return $len;
			}
		);

		$headers = array();
		$headers[] = "Content-Type: application/json";
		$headers[] = "Accept: application/json";
		curl_setopt($chservice, CURLOPT_HTTPHEADER, $headers);
		$result = curl_exec($chservice);
		if (curl_errno($chservice)) {
			echo 'Error:' . curl_error($chservice);
		}
		curl_close ($chservice);
		if(isset($headers['set-cookie'])){
			foreach($headers['set-cookie'] as $cookie){
				if(strpos($cookie, 'PLAY') !== false){
					$play_session = explode(';', $cookie);
					 $cookie_val = $play_session[0];
					break;
				}
			}
		}
		$chserviceub = curl_init();
		curl_setopt( $chserviceub, CURLOPT_COOKIE, $cookie_val );
		curl_setopt($chserviceub, CURLOPT_URL, "https://ubookr.com/api/services/12562");
		curl_setopt($chserviceub, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($chserviceub, CURLOPT_CUSTOMREQUEST, "GET");
		$headers = array();
		$headers[] = "Accept: application/json";
		curl_setopt($chserviceub, CURLOPT_HTTPHEADER, $headers);
		$result = curl_exec($chserviceub);
		if (curl_errno($chserviceub)) {
			echo 'Error:' . curl_error($chserviceub);
		}
		curl_close ($chserviceub);
		$service_data = json_decode($result); 
		pr($service_data); die;
		$categoryName = TableRegistry::get('Category');
		$query = $categoryName->query();
		$query->update()
			->set(['ubookr_id' => $service_data->serviceType->id])
			->where(['cat_name LIKE' => $service_data->serviceType->name])
			->execute();
		$verifycategory = $categoryName->find('all',['conditions'=>['cat_name' =>$service_data->serviceType->name]])->first();
		$service_cost = TableRegistry::get('ServiceCosts');
		$query = $service_cost->query();
		$query->update()
			->set(['ubookr_service_id' => $service_data->id ,'duration'=> $service_data->duration])
			->where(
			array(
			 'or' => array(
						  'ubookr_service_id' =>$service_data->id,
						  'type' =>$service_data->name
						  )
				)
				)
			->execute();	
		//return $this->redirect('/admin/user/list-service');			
	}

	public function ubooker()
    {
		$this->autoRender = false;
		$this->viewBuilder()->layout(false);
		$signer = new Sha512();
		$token = (new Builder())->setIssuedAt(time()) // Configures the time that the token was issue (iat claim)
							   ->setExpiration(time() + 3600) // Configures the expiration time of the token (exp claim)
							   ->set('user_id', '27605') // Configures a new claim, called "user_id" with YOUR user_id (as a string)
							   ->sign($signer, 'pSGjWhbAhAgyuB96eBa1raPOZPM6') // creates a signature using YOUR secret
							   ->getToken(); // Retrieves the generated token
		
		// Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://ubookr.shanness.dyndns.org/api/services?serviceType=23&postcode=2022");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");


		$headers = array();
		$headers[] = "Ubookr-Jws: ".$token;
		$headers[] = "Accept: application/json";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		}
		curl_close ($ch);
		$service_data = json_decode($result); 
		

		
		
		
		
		
		pr($service_data); die;
		echo $token; die();
		$this->set(compact('token'));
		
	}
	
	
}

