<script src="https://ubookr.com/extras/ubookr-widget/ubookr-widget-bootstrap-2.0.min.js"></script>
<?php use Cake\Routing\Router; ?>
<div class="main-container">
            <section class="backgound_image">
                <div class="container">
                    <div class="common_form registration form_add_property realstate_myfiles">
                        <div class="common_form_right">
                            <div class="common_section">
                                <h2 class="common_h2">My Projects</h2>
								<?php 
								if(!empty($properties_details)){ 
								foreach($properties_details as $details) {?>
                                <div class="filesRow">
                                    <div class="col-relative">
                                        <div class="col left">
										<input type="hidden" id="propaddress_<?php echo $details['id']; ?>" value="<?php echo $details['street_address']; ?>">
										<input type="hidden" id="proppin_<?php echo $details['id']; ?>" value="<?php echo $details['pin']; ?>">
										<input type="hidden" id="propcityname_<?php echo $details['id']; ?>" value="<?php echo $details['city']['name']; ?>">
										<input type="hidden" id="phone" value="<?php echo $loggedIn['phone']; ?>">
										<input type="hidden" id="email" value="<?php echo $loggedIn['email']; ?>">
										<input type="hidden" id="name" value="<?php echo $loggedIn['first_name']." ".$loggedIn['last_name']; ?>">
                                      <h3><?php echo $details['property_title']; ?></h3>
                                      <p class="fileAddress"><?php echo $details['street_address']; ?> <?php echo $details['pin']; ?></p>
                                      <p class="fileDate"><?php  echo date("jS F, Y", strtotime($details['created'])); ?></p>
									  <?php $property=array();   ?>
									  <?php foreach($details['property_service'] as $service_dtls) { 									  
									  $property[$service_dtls['category']['cat_name']][$service_dtls['service_cost']['type']]['id']=$service_dtls['service_cost']['type'];
									  $property[$service_dtls['category']['cat_name']][$service_dtls['service_cost']['type']]['ubookr_service_id']=$service_dtls['service_cost']['ubookr_service_id'];
									  $property[$service_dtls['category']['cat_name']][$service_dtls['service_cost']['type']]['supplierId']=$service_dtls['service_cost']['supplierId'];
									  $property[$service_dtls['category']['cat_name']][$service_dtls['service_cost']['type']]['id']=$service_dtls['id'];
									  $property[$service_dtls['category']['cat_name']][$service_dtls['service_cost']['type']]['drop_date']=$service_dtls['drop_date'];
									  $property[$service_dtls['category']['cat_name']][$service_dtls['service_cost']['type']]['drop_link']=$service_dtls['drop_link'];
									  $property[$service_dtls['category']['cat_name']][$service_dtls['service_cost']['type']]['order_id']=$service_dtls['order_id'];
									  $property[$service_dtls['category']['cat_name']][$service_dtls['service_cost']['type']]['order_date']=$service_dtls['order_date'];
									  $property[$service_dtls['category']['cat_name']][$service_dtls['service_cost']['type']]['change_status']=$service_dtls['change_status'];
									  $property[$service_dtls['category']['cat_name']][$service_dtls['service_cost']['type']]['service_id']=$service_dtls['service_id'];
									  } 
									  ?>
                                   </div>
                                        <div class="col left">
                                     <p>Cost</p>
                                     <p class="filePrice">AUD <?php echo $details['cost']; ?></p>
                                   </div>
                                        <div class="col payment-status">
									<?php if($details['payment_status']=="N") { ?>
									 <p>*<a href="<?php echo Router::url('/property/payment-process/'.base64_encode($details['id']));?>">Payment Request</a></p>
									<?php } else{  ?>
									<p>Paid</p>
									<?php } ?>
                                   </div>
                                    </div>    
                                    <div class="list_product">
										<?php  foreach($property as $key => $pro_val){ ?>
											<ul>
											<?php foreach($pro_val as $key1 =>$val){  ?>
                                                <div class="list_product-relative-wrap">
												<h2><?php echo $key;?></h2>
												<li><?php echo $key1;?></li>
												<?php if((!empty($val['drop_link'])) && $val['change_status']=="I") { ?>
                                                        <div class="list_product-absolute-wrap">
                                                             <p>Files delivered</p>
                                                            <p><a href="<?php echo $val['drop_link']; ?>" class="commonBtn" target="_blank" onclick="downloadFile('<?php echo base64_encode($details["id"]); ?>')">Download</a></p>
                                                        </div>
																<?php 
																$start_time = date("d-m-Y g:iA");
																$end_time = date("d-m-Y g:iA", strtotime($val['drop_date']));
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
																if($val['drop_date']!=""){
																if($duration == ''){
																	$duration = 0;
																}
																}else{
																	$duration = 0;
																}
																$duration."<br>"; 
																if($duration<15){
															 ?>
															 <p>*<a href="javascript:void(0);" data-toggle="modal" data-target="#uploadmodal1"  class="requestLink" onclick="PropertyIdRetrive(<?php echo $val['id']; ?>)">Change Request</a></p>
																<?php } ?>
															<?php } else { ?>
															<div class="list_product-absolute-wrap"><p class="notDelivered">Files Pending</p></div>
															<?php } ?>
														<?php  if ($val['service_id']!=5) {
																if($val['order_id']=="" || $val['order_id']==0 ){
															?>	
															<p class="booking_service"><a href="javascript:void(0);" class="commonBtn"  onclick="return bookService(<?php echo $val['ubookr_service_id']; ?>,<?php echo $val['supplierId']; ?>,<?php echo $details['id']; ?>,<?php echo $val['id']; ?>)">Book Service</a></p>
														<?php } else { ?>
														<p class="booking_service"><a href="javascript:void(0);" class="commonBtn"  ><?php echo $val['order_id'] ;?></a></p>
														<p class="booking_service"><a href="javascript:void(0);" class="commonBtn"  ><?php echo $val['order_date'] ;?></a></p>
														<?php } } ?>
														<!--<div id="loader-content-<?php echo $val['id']; ?>" style='display:none' class="s_loader">
															<img id="loader-img"  src="<?php echo $this->request->webroot; ?>images/uberRE.gif" alt="loader.." />
														</div>-->
                                                </div>   
											<?php }?>
											</ul>
										<?php } ?>
                                    </div>
                                </div>
							<?php }   ?>
								<?php }else { ?>
								No record Found
								<?php }?>
                            </div>
                        </div>
						<?php if(!empty($properties_details)){  ?>
                        <p class="notetxt">*Please note: only one change request is allowed for each project</p>
						<?php } ?>
                    </div>
                </div>
            </section>
        </div>
<div id="uploadmodal1" class="modal in" role="dialog" >
    <div class="vertical-alignment-helper">
        <div class="modal-dialog vertical-align-center">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" title="Close" value="close">
                        <img src="/uberre/images/closeIcon1.png" alt="closeIcon"></button>
                </div>
              <div class="modal-body">
                    <?php  echo $this->Form->create('project', ['url' => ['controller' => 'project','action' => 'change_link','prefix' => false,],'novalidate' => 'novalidate','id'=>'change_service']); ?>
                        <div class="form-group">
                            <label class="common_h2">Change Request</label>
                            <div class="input textarea required" aria-required="true">
							<?php echo $this->Form->input('change_msg', ['type' => 'textarea', 'required' => true, 'class' => 'form-control','value'=>'', 'placeholder'=>"Change Message",'label' => false]); ?>
							<input type="hidden" name="property_id" id="property_fetch_id">
							</div>
                        </div>
                        <div class="form-group login text-center">
                            <input class="btnBlue" value="Submit" type="submit">
                        </div>
                     <?php echo $this->Form->end(); ?>
                </div>   
                
        </div>
    </div>
</div>
</div>


    <script>
	$('#change_service').validate();
	function PropertyIdRetrive(e) {
		var pro_id = e;
		$("#property_fetch_id").val(pro_id);
	}
	</script>
<script>
    $(function() {
   $('input').filter('.datepick').datepicker({
    dateFormat: "DD, d MM",
       
   });
  });
    $( document ).ready(function() {
  $(function() {    
    $('.datepick').datepicker();
     $( '.datepick' ).datepicker( "option", "dateFormat", "DD, d MM" );
    $('.datepick').datepicker('setDate', new Date()); 
});    
});
  </script>
<script>
  $('.datepick').each(function(){
    $(this).datepicker({
         minDate: 0, 
    });
});
</script>
<script>
$('.datepick').datepicker();
$('.next-day').on("click", function () {
    var date = $('.datepick').datepicker('getDate');
     date.setDate(date.getDate() + 1)
    $('.datepick').datepicker("setDate", date);
});
$('.prev-day').on("click", function () {
    var date = $('.datepick').datepicker('getDate');
     date.setDate(date.getDate() - 1)
    $('.datepick').datepicker("setDate", date);
});
function ubooker_service(prop_id) {
	var property_id = prop_id;
	$('#loader-content-'+property_id).show();
	if (property_id != '') {
		jQuery.ajax({
			type: "post",
			url: '<?php echo Router::url("/users/ubooker-service/",true); ?>',
			data: {
				'property_id': property_id
			},
			success: function(response) {
				$('#ubookr_timeslot_'+property_id).html(response);
				$('#loader-content-'+property_id).hide();
			}
		});
	}
 }	
</script>

<script type="text/javascript">
var ubookrEvent;
document.addEventListener("ubookrready", function(ue) {
    ubookrEvent = ue;
});
	function bookService(serviceId,supplierId,property_id,service_id) {
				var property=property_id;
				var prop_service_id=service_id;
                var proptitle=$('#proptitle_'+property).val();
                var propaddress=$('#propaddress_'+property).val();
                var proppin=$('#proppin_'+property).val();
                var propcityname=$('#propcityname_'+property).val();
                var phone=$('#phone').val();
                var email=$('#email').val();
                var name=$('#name').val();
                ubookrEvent.detail.createWidget({
                        apiPath: "https://ubookr.com/api/",
                        scheme: "timeonly", //Don't change
                        supplierId: supplierId, //ADD SUPPLIER ID
                        jwtToken: "<?php echo $token; ?>",
                        schemeParams: { //Fill all these properties (mobile & office number optional)
                                serviceId: serviceId,
                                email: email,
                                mobilePhoneNumber: phone,
								officePhoneNumber: phone,
                                notes: "",
                                address: { //All fields required; ensure postcode is a string
                                        line1: propaddress,
                                        suburb: propcityname,
                                        state: "NSW",
                                        postcode: proppin,
                                        country: "AU"
                                },
                                onBookingSaved: function(booking) { //Whatever handling you need for when the widget closes
								console.log("onBookingSaved callback. Booking ID: " + booking.id, booking); 
									var date_booking =booking.start;
									var id_booking =booking.id;
										jQuery.ajax({
											type: "post",
											url: '<?php echo Router::url("/project/ubooker-service-booking/",true); ?>',
											data: {
												'prop_service_id': prop_service_id,'date_booking':date_booking,'id_booking':id_booking
											},
											success: function(response) {
												if(response==1){
													location.reload();
												}
											}
										});
								}
                        }
                }).open();
		
	}
</script>

<script>
function downloadFile(property_id){
	$.ajax({
		type:'post',
		url:'<?php echo Router::url("/project/download-file/",true); ?>'+property_id,
	});
	
}

</script>