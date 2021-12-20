<style>
	<?php
	$entitlements = $this->db->get("entitlement")->result_object();
	$priviledges_style='';
	foreach ($entitlements as $entitlement) :

		$priviledges_style .= "." . $entitlement->name . "{display:none;}";

	endforeach;

	foreach ($entitlements as $entitlement) :
		if ($this->crud_model->user_privilege($this->session->profile_id, $entitlement->name)) {

			$priviledges_style .= "." . $entitlement->name . "{display:block;}";


			if ($entitlement->derivative_id !== 0) {

				$first_parent_obj = $this->db->get_where("entitlement", array("entitlement_id" => $entitlement->derivative_id));

				if ($first_parent_obj->num_rows()>0) {

					$first_parent = $first_parent_obj->row();

					$priviledges_style .=  "." . $first_parent->name . "{display:block;}";

					if ($first_parent->derivative_id !== 0) {

						$second_parent_obj=$this->db->get_where("entitlement", array("entitlement_id" => $first_parent->derivative_id));
						if($second_parent_obj->num_rows()>0){

							$second_parent = $second_parent_obj->row();

							$priviledges_style .=  "." . $second_parent->name . "{display:block;}";
	
							if ($second_parent->derivative_id !== 0) {

								$third_parent_obj=$this->db->get_where("entitlement", array("entitlement_id" => $second_parent->derivative_id));

								if($third_parent_obj->num_rows()>0){

									$third_parent = $third_parent_obj->row();
	
									$priviledges_style .=  "." . $third_parent->name . "{display:block;}";
		
									if ($third_parent->derivative_id !== 0) {

										$fourth_parent_obj = $this->db->get_where("entitlement", array("entitlement_id" => $third_parent->derivative_id));

										if($fourth_parent_obj->num_rows()>0){

											$fourth_parent = $fourth_parent_obj->row();
		
											$priviledges_style .=  "." . $fourth_parent->name . "{display:block;}";

										}

									
									}

								}
							
							}

						}
					
					}
				}
			}
		}
	endforeach;

	echo $priviledges_style;
	
	?>


</style>

