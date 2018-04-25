<?php
namespace App\Controller;
use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Mailer\Email;
use Cake\Routing\Router;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha512;
/**
 * Sites Controller is for manage all the cms pages and and home page of the site.
 *
 * @property \Admin\Model\Table\Cms $Cms
 */
class ProjectController extends AppController
{
	public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->loadComponent('Email');
        $this->Auth->allow(['completedProjectsCron']);
    }
    
    /**
     * [index function is for home page of the site]
     * 
     */
	  public function realstateMyFile(){
			$loggedIn = $this->Auth->user();
			$property = TableRegistry::get('Property');
			$properties_details=$property->find('all',['contain'=>['PropertyService','PropertyService.Category','PropertyService.ServiceCosts','City'],'conditions'=>['user_id'=>$loggedIn['id']],'order'=>['Property.id'=>'DESC']])->toArray();
			//pr($properties_details); die;
			$signer = new Sha512();
			$token = (new Builder())->setIssuedAt(time()) // Configures the time that the token was issue (iat claim)
								   ->setExpiration(time() + 3600) // Configures the expiration time of the token (exp claim)
								   ->set('user_id', '27605') // Configures a new claim, called "user_id" with YOUR user_id (as a string)
								   ->sign($signer, 'pSGjWhbAhAgyuB96eBa1raPOZPM6') // creates a signature using YOUR secret
								   ->getToken(); // Retrieves the generated token			
			$this->set(compact('properties_details','token','loggedIn'));
		  
	  }
	  
	  
	  public function ubookerServiceBooking()
    {
		$this->autoRender = false;
		$this->viewBuilder()->layout(false);
		if ($this->request->is('post')) {
			$this->loadModel('PropertyService');
			$query = $this->PropertyService->query();
			$query->update()
				->set(['order_id' => $this->request->data['id_booking'],'order_date' => $this->request->data['date_booking']])
				->where(['id' => $this->request->data['prop_service_id']])
				->execute();
		}
		echo 1;
		die();
	}
	  
	  
	  
	   public function currentlyAssignJob(){
		$loggedIn = $this->Auth->user();
		//pr($loggedIn); die;
		$property = TableRegistry::get('Property');
		$properties_details_service=$property->find('all',['contain'=>['PropertyService','PropertyService.Category','PropertyService.ServiceCosts'],'conditions'=>['assign_by_admin'=>$loggedIn['id']],'order'=>['Property.id'=>'DESC']])->toArray();
		//pr($properties_details); die;
		$this->set(compact('properties_details_service'));
	  }
	  
	  
	  public function addLink(){
		  if ($this->request->is('post')) {
			//$property = TableRegistry::get('Property');
			$property_service = TableRegistry::get('PropertyService');
			$query = $property_service->query();
			$query->update()
				->set(['drop_link' => $this->request->data['drop_link'],'change_status' => "I",'drop_date'=>date('Y-m-d h:i:s')])
				->where(['id' => $this->request->data['property_id']])
				->execute();
			$this->Flash->success(__('Your link has been updated successfully.'));
			$this->redirect(array('controller'=>'project','action'=>'currentlyAssignJob'));
		  }
	  }
	  
	   public function changeLink(){
		if ($this->request->is('post')) {
			//$property = TableRegistry::get('Property');
			$property_service = TableRegistry::get('PropertyService');
			$query = $property_service->query();
			$query->update()
				->set(['change_msg' => $this->request->data['change_msg']])
				->where(['id' => $this->request->data['property_id']])
				->execute();
			$this->Flash->success(__('Your change request  has been updated successfully.'));
			$this->redirect(array('controller'=>'project','action'=>'realstateMyFile'));
		}
	  }
	  
	  
	   /* public function completedProjects(){
		$loggedIn = $this->Auth->user();
		$property = TableRegistry::get('Property');
		$properties_details_service_check=$property->find('all',['conditions'=>['assign_by_admin'=>$loggedIn['id']],'order'=>['Property.id'=>'DESC']])->toArray();
		//pr($properties_details_service_check); die;
		foreach($properties_details_service_check as $direct_job){
			$start_time = date("d-m-Y g:iA");
			$end_time = date("d-m-Y g:iA", strtotime($direct_job['drop_date']));
			$date2 = new \DateTime($end_time);
			$date1 = new \DateTime($start_time);
			$date= $date1->diff($date2);
			$year = "";
			$month = "";
			$days = "";
			$hour = "";
			$min = "";
			$sec = "";
			if( $date->format('%m') != "0" ) {
				$month = $date->format('%m').__( " Month ");
			}
			if( $date->format('%d') != "0" ) {
				$days = $date->format('%d').__(" Days ");
			}
			if( $date->format('%h') != "0" ) {
				$hour = $date->format('%h');
			}
			if( $date->format('%i') != "0" ) {
				$min = $date->format('%i').__(" Minute ");
			}
			if( $date->format('%s') != "0" ) {
				$sec = $date->format('%s').__(" Seconds " );
			}
			$duration = $hour;
			if($duration == ''){
				$duration = 0;
			}
			//echo $duration."<br>";
			if($duration>=1){
				$query = $property->query();
								$query->update()
									->set(['project_status' => 'C'])
									->where(['assign_by_admin' => $loggedIn['id']])
									->execute();	
			}
		}
		$properties_details_service=$property->find('all',['contain'=>['PropertyService','PropertyService.Category','PropertyService.ServiceCosts'],'conditions'=>['assign_by_admin'=>$loggedIn['id'],'project_status'=>"C"],'order'=>['Property.id'=>'DESC']])->toArray();
		//pr($properties_details_service); die;
		$this->set(compact('properties_details_service'));
	  } */
	  
	  public function completedProjects(){
		  $service_provider=$this->Auth->user();
		  $property = TableRegistry::get('Property');
		  $property_details=$property->find('all',['conditions'=>['Property.assign_by_admin'=>$service_provider['id'],'Property.project_status'=>'N'],'order'=>['Property.id'=>'DESC'],'contain'=>['PropertyService']])->toArray();
		  //pr($property_details);die;
		  foreach($property_details as $new_property_details){
			  foreach($new_property_details['property_service'] as $service_details){
				  if(isset($service_details['drop_link']) && !empty($service_details['drop_link'])){
					$start_time = date("d-m-Y g:iA");
						$end_time = date("d-m-Y g:iA", strtotime($service_details['drop_date']));
						$date2 = new \DateTime($end_time);
						$date1 = new \DateTime($start_time);
						$date= $date1->diff($date2);
						$year = "";
						$month = "";
						$days = "";
						$hour = "";
						$min = "";
						$sec = "";
						if( $date->format('%m') != "0" ) {
							$month = $date->format('%m').__( " Month ");
						}
						if( $date->format('%d') != "0" ) {
							$days = $date->format('%d').__(" Days ");
						}
						if( $date->format('%h') != "0" ) {
							$hour = $date->format('%h');
						}
						if( $date->format('%i') != "0" ) {
							$min = $date->format('%i').__(" Minute ");
						}
						if( $date->format('%s') != "0" ) {
							$sec = $date->format('%s').__(" Seconds " );
						}
						$duration = $hour;
						if($duration == ''){
							$duration = 0;
						}
			
						if($duration>=1){
							$query = $property->query();
											$query->update()
												->set(['project_status' => 'C'])
												->where(['assign_by_admin' => $service_provider['id']])
												->execute();	
						}
					  
				  }
				  
			  }
			  
		  }
		 $properties_details_service=$property->find('all',['contain'=>['PropertyService','PropertyService.Category','PropertyService.ServiceCosts'],'conditions'=>['Property.assign_by_admin'=>$service_provider['id'],'Property.project_status'=>"C"],'order'=>['Property.id'=>'DESC']])->toArray();
		$this->set(compact('properties_details_service'));
	  }
	  
	  
	/************************************Cron Job Set Of  service provider  completed satatus*************************************************/
	  
	public function completedProjectsCron(){  
		$this->autoRender = false;
		$this->viewBuilder()->layout('ajax');
		$property = TableRegistry::get('Property');
		$properties_check=$property->find('all',['order'=>['Property.id'=>'DESC']])->toArray();
		foreach($properties_check as $direct){
			$start_time = date("d-m-Y g:iA");
			$end_time = date("d-m-Y g:iA", strtotime($direct['drop_date']));
			$date2 = new \DateTime($end_time);
			$date1 = new \DateTime($start_time);
			$date= $date1->diff($date2);
			 $year = "";
			$month = "";
			$days = "";
			$hour = "";
			$min = "";
			$sec = "";
			if( $date->format('%m') != "0" ) {
				$month = $date->format('%m').__( " Month ");
			}
			if( $date->format('%d') != "0" ) {
				$days = $date->format('%d').__(" Days ");
			}
			if( $date->format('%h') != "0" ) {
				$hour = $date->format('%h');
			}
			if( $date->format('%i') != "0" ) {
				$min = $date->format('%i').__(" Minute ");
			}
			if( $date->format('%s') != "0" ) {
				$sec = $date->format('%s').__(" Seconds " );
			}
			$duration = $hour;
			if($duration == ''){
				$duration = 0;
			}
			//echo $duration."<br>";
			//echo "id==>".$direct['id']."<br>"; 
			if($duration>=1){
				$query = $property->query();
								$query->update()
									->set(['project_status' => 'C'])
									->where(['id' => $direct['id']])
									->execute();	
			}
		}
	}
	/************************************ End Cron Job Set Of  service provider  completed satatus*******************************************/
	
		/*************************************start download uploaded file from real estate*************************/
	
	public function downloadFile($property_id=null){
		$this->autoRender=false;
		$this->viewBuilder()->layout('ajax');
		if($property_id==null){
			 throw new NotFoundException(__('Page not found'));
		}
		$property_id=base64_decode($property_id);
		$this->loadModel('Property');
		$property_details=$this->Property->find('all',['conditions'=>['Property.id'=>$property_id],'contain'=>'realestate'])->first();
		$settings=$this->getSiteSettings();
		$this->Email->downloadFile($property_details,$settings);
	
	}
	/************************************* download uploaded file from real estate end*************************/

	  
}