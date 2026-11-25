$(document).ajaxStart(function(){
    $.blockUI({ css: { 
            border: 'none', 
            padding: '15px', 
            backgroundColor: '#000', 
            '-webkit-border-radius': '10px', 
            '-moz-border-radius': '10px', 
            opacity: .5, 
            color: '#fff',
            'z-index':10051
        } });
});



$(document).ajaxSuccess(function(){
    $.unblockUI();
});

$('#account_type').change(function () {
    var account_type = $('#account_type').val();
    if (account_type === 'loss') {
        $('#loss_type_div').show();
        $('#loss_type').attr('required', 'true');
    } else {
        $('#loss_type_div').hide();
        $('#loss_type').removeAttr('required', 'true');
    }
});

$("input[name='send_sms']").change(function () {
    var send_sms = $("input[name='send_sms']:checked").val();
    if (send_sms === '1') {
        $('#mobile_no').attr('required', 'true');
    } else {
        $('#mobile_no').removeAttr('required', 'true');
    }
});

function download_cust_recent_file(cust_id) {
    $('.download_show_hide_' + cust_id).toggle();
    var base_url = $('#base_url').val();
    var url = base_url + 'master/download_recent_cust_ledger_file';
    $.get(url, {
        cust_id: cust_id
    }, function (data) {
        if (data) {
            window.location.replace(base_url + "master/download_recent_cust_ledger_file?cust_id=" + cust_id);
            $('.download_show_hide_' + cust_id).toggle();
        }
    });
}
function download_daily_report_recent_file(file_name, index) {
    $('.download_show_hide_' + index).toggle();
    var base_url = $('#base_url').val();
    var url = base_url + 'master/download_recent_daily_report_file/' + file_name;
    $.get(url, {
    }, function (data) {
        if (data) {
            window.location.replace(base_url + 'master/download_recent_daily_report_file');
            $('.download_show_hide_' + index).toggle();
        }
    });
}

function delete_cust_recent_file(cust_id) {
    $('.delete_show_hide_' + cust_id).toggle();
    swal({
        title: "Are you sure?",
        text: "You will not be able to recover this file!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#EF5350",
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "No, cancel pls!",
        closeOnConfirm: false,
        closeOnCancel: false
    },
    function (isConfirm) {
        if (isConfirm) {
            var base_url = $('#base_url').val();
            var url = base_url + 'master/delete_recent_cust_ledger_file';
            $.post(url, {
                cust_id: cust_id
            }, function (data) {
                if (data === 'true') {
                    $('#recent_cust_file_' + cust_id).remove();
                    swal({
                        title: "Deleted!",
                        text: "Your file has been deleted.",
                        confirmButtonColor: "#66BB6A",
                        type: "success"
                    });
                } else {
                    swal({
                        title: "Cancelled from Server",
                        text: "Your file is safe !",
                        confirmButtonColor: "#2196F3",
                        type: "error"
                    });
                    $('.delete_show_hide_' + cust_id).toggle();
                }
            });
        }
        else {
            swal({
                title: "Cancelled",
                text: "Your file is safe !",
                confirmButtonColor: "#2196F3",
                type: "error"
            });
            $('.delete_show_hide_' + cust_id).toggle();
        }
    });
}

function delete_daily_report_recent_file(file_name, index) {
    //alert(index);
    $('.delete_show_hide_' + index).toggle();
    swal({
        title: "Are you sure?",
        text: "You will not be able to recover this file!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#EF5350",
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "No, cancel pls!",
        closeOnConfirm: false,
        closeOnCancel: false
    },
    function (isConfirm) {
        if (isConfirm) {
            var base_url = $('#base_url').val();
            var url = base_url + 'master/delete_recent_daily_report_file/' + file_name;
            $.post(url, {
            }, function (data) {
                //alert(data);
                if (data === 'true') {
                    $('#file_name').remove();
                    // $('#recent_daily_report_file').html('<td colspan="4" class="text-center"><p>There is no result for you.</p></td>');
                    swal({
                        title: "Deleted!",
                        text: "Your file has been deleted.",
                        confirmButtonColor: "#66BB6A",
                        type: "success"
                    });
                } else {
                    swal({
                        title: "Cancelled from Server",
                        text: "Your file is safe !",
                        confirmButtonColor: "#2196F3",
                        type: "error"
                    });
                    $('.delete_show_hide_' + index).toggle();
                }
            });
        }
        else {
            swal({
                title: "Cancelled",
                text: "Your file is safe !",
                confirmButtonColor: "#2196F3",
                type: "error"
            });
            $('.delete_show_hide_' + index).toggle();
        }
    });
}

$("input[name='transactional_mode']").change(function () {
    var transactional_mode = $("input[name='transactional_mode']:checked").val();
    if (transactional_mode === 'MT' || transactional_mode === 'OR') {
        $('.MT').show();
        $('.RK').hide();
        $('#weight').attr('required', 'true');
        $('#purity').attr('required', 'true');
        $('#amount').removeAttr('required', 'true');
        $('#amount').val('');
        $('.1').attr('disabled', 'disabled');
        $('#transactional_type_div').hide();
    } else {
        $('#transactional_type_div').show();
        $('#amount').attr('required', 'true');
        $('#weight').removeAttr('required', 'true');
        $('#purity').removeAttr('required', 'true');
        $('#weight').val('');
        $('#purity').val('');
        $('.1').removeAttr('disabled', 'disabled');
        $('.MT').hide();
        $('.RK').show();
    }
    if(transactional_mode == 'OR'){
    	$('.show_collection').show();
    }else{
    	$('.show_collection').hide();
    }
    var transactional_type = $('input[name="transactional_type"]:checked').val();
    if (transactional_type != undefined) {
        //alert(transactional_type);
        if ((transactional_type == 'receipt') && (transactional_mode === 'MT' || transactional_mode === 'OR')) {
            $('.key').hide();
            $('.PW').hide();
        } else {
            if (transactional_mode != 'RK') {
                $('.PW').show();
            }
            $('.key').show();
        }
    }



});



$('#remark').change(function () {
    var remark = $('#remark').val();
    if (remark === 'other') {
        $('.other_remark_row').show();
        $('#other_remark').attr('required', 'true');
    } else {
        $('.other_remark_row').hide();
        $('#other_remark').removeAttr('required');
    }
});

$('.filter').change(function () {
    var filter_by_user = $('#filter_by_user').val();
    var filter_by_cust = $('#filter_by_cust').val();
    var base_url = $('#base_url').val();
    window.location.replace(base_url + "report/view_report?user_private_key=" + filter_by_user + "&cust_private_key=" + filter_by_cust);
});

$(function () {
    $('#cust_fieled_toggle').bind('click', function () {
        if ($(this).is(':checked')) {
            $('.cust_fieled').show();
            $('.cust_fieled_total').attr('colspan', 2);
        } else {
            $('.cust_fieled').hide();
            $('.cust_fieled_total').attr('colspan', 1);
        }
    });
});
$(function () {
    $('#user_fieled_toggle').bind('click', function () {
        if ($(this).is(':checked')) {
            $('.user_fieled').show();
        } else {
            $('.user_fieled').hide();
        }
    });
});
$(function () {
    $('#remark_fieled_toggle').bind('click', function () {
        if ($(this).is(':checked')) {
            $('.remark_fieled').show();
        } else {
            $('.remark_fieled').hide();
        }
    });
});

$("#approved").change(function () {
    if (this.checked) {
        $('#export_daily_report').removeAttr('');
    } else {
        $('#export_daily_report').attr('');
    }
});

$('#key1').blur(function () {
    var key1 = $('#key1').val();
    if (key1) {
        $('#key1').attr('required', 'true');
        $('#val1').attr('required', 'true');
    } else {
        $('#key1').removeAttr('required', 'true');
        $('#val1').removeAttr('required', 'true');
        $('#key1-error').hide();
        $('#val1-error').hide();
        $('#key1').val('');
        $('#val1').val('');
    }
});
$('#key2').blur(function () {
    var key2 = $('#key2').val();
    if (key2) {
        $('#key2').attr('required', 'true');
        $('#val2').attr('required', 'true');
    } else {
        $('#key2').removeAttr('required', 'true');
        $('#val2').removeAttr('required', 'true');
        $('#key2-error').hide();
        $('#val2-error').hide();
        $('#key2').val('');
        $('#val2').val('');
    }
});

$('#key3').blur(function () {
    var key3 = $('#key3').val();
    if (key3) {
        $('#key3').attr('required', 'true');
        $('#val3').attr('required', 'true');
    } else {
        $('#key3').removeAttr('required', 'true');
        $('#val3').removeAttr('required', 'true');
        $('#key3-error').hide();
        $('#val3-error').hide();
        $('#key3').val('');
        $('#val3').val('');
    }
});

$('#key4').blur(function () {
    var key4 = $('#key4').val();
    if (key4) {
        $('#key4').attr('required', 'true');
        $('#val4').attr('required', 'true');
    } else {
        $('#key4').removeAttr('required', 'true');
        $('#val4').removeAttr('required', 'true');
        $('#key4-error').hide();
        $('#val4-error').hide();
        $('#key4').val('');
        $('#val4').val('');
    }
});



function verification_raw_waves(private_key) {
    var base_url = $('#base_url').val();
    var url = base_url + 'raw_waves/verified_raw_waves';
    success_flag = true;
    if($('#transaction_type').val() == 'receipt'){
    	mapped_cust_id = $('#mapped_cust_id').val();
    	scale_weight = parseFloat($('#scale_weight').val().trim());
    	
    	less_weight = parseFloat($('#less_weight').val().trim());
    	
		
		if (isNaN(scale_weight) || isNaN(less_weight)) {
		    alert('Less Weight / Scale weight cannot be blank.');
		    return; 
		}else{
			net_weight = (scale_weight - less_weight);
			    	
	    	stone_percentage = (less_weight / net_weight) * 100;
	    	stone_percentage = stone_percentage.toFixed(1);
	    	gross_weight = parseFloat($('#gross_weight').val());
	    	weight_variance = ((scale_weight * 1000) - (gross_weight * 1000))/1000; 
	    	weight_variance = Math.abs(weight_variance);
	    	if(weight_variance > 0.030){
				success_flag = false;
			}
		}
    }else{
    	mapped_cust_id = null;
    	scale_weight = null;
    	stone_percentage = 0;
    	scale_weight = gross_weight = parseFloat($('#gross_weight').val());
    }
    
    
    if(success_flag == false){
		swal({
                title: "Something went wrong!",
                text: "Please Check Entered Weight...",
                confirmButtonColor: "#EF5350",
                type: "error"
            });
	}else{
		swal({
	        title: "Less Percentage is "+stone_percentage+"%",
	        text: "Do you want to proceed? ",
	        type: "warning",
	        showCancelButton: true,
	        confirmButtonColor: "#4caf50",
	        confirmButtonText: "Yes, Proceed!",
	        cancelButtonText: "No, cancel pls!",
	        closeOnConfirm: true,
	        closeOnCancel: true
	    },
	    function (isConfirm) {
			
			if (isConfirm) {
	           $.post(url, {
			        private_key: private_key,
			        mapped_cust_id: mapped_cust_id,
			        scale_weight: scale_weight
			    }, function (data) {
			        var result = jQuery.parseJSON(data);
			
			        if (result.status === 'true') {
			            $('#ajaxModal').modal('toggle');
			            $('#ajaxModal').remove();
			            $('#view_voucher_model').remove();
			
			
			            if (result.transactional_type == 'receipt') {
			                var base_url = $('#base_url').val();
			                var url = base_url + "process_waves/transfer_process_waves/" + result.to_id + '/' + result.process_waves_id + '/false/' + private_key;
			                var $this = $(this)
			                        , $remote = url
			                        , $modal = $('<div class="modal" id="add_edit_process_waves_model"><div class="modal-body"></div></div>');
			                $('.modal-body').append($modal);
			                $modal.modal();
			                $modal.load($remote);
			            } else {
			                var handledElse = false;
			                var finishCloseElse = function () {
			                    if (handledElse) return;
			                    handledElse = true;
			                    if (typeof swal !== 'undefined' && swal.close) {
			                        swal.close();
			                    }
			                    $('.sweet-alert, .sweet-overlay').hide();
			                    $('#verification_code').val('');
			                    $('#panding_raw_waves_edit_li_' + private_key).remove();
			                    $('#panding_raw_waves_status_td_' + private_key).removeClass('text-danger');
			                    $('#panding_raw_waves_status_td_' + private_key).addClass('text-success');
			                    $('#panding_raw_waves_status_td_' + private_key).text('Approved');
			                    window.location.reload();
			                };

			                swal({
			                    title: "Success!",
			                    text: "Voucher Verified Successfully!",
			                    confirmButtonColor: "#66BB6A",
			                    type: "success",
			                    timer: 2000,
			                    closeOnConfirm: true
			                }, function () {
			                    finishCloseElse();
			                });

			                setTimeout(function () {
			                    finishCloseElse();
			                }, 2000);
			            }

			            $('#add_edit_process_waves_model').on('hidden.bs.modal', function () {
			                var handledHidden = false;
			                var finishCloseHidden = function () {
			                    if (handledHidden) return;
			                    handledHidden = true;
			                    if (typeof swal !== 'undefined' && swal.close) {
			                        swal.close();
			                    }
			                    $('.sweet-alert, .sweet-overlay').hide();
			                    $('#verification_code').val('');
			                    $('#panding_raw_waves_edit_li_' + private_key).remove();
			                    $('#panding_raw_waves_status_td_' + private_key).removeClass('text-danger');
			                    $('#panding_raw_waves_status_td_' + private_key).addClass('text-success');
			                    $('#panding_raw_waves_status_td_' + private_key).text('Approved');
			                    window.location.reload();
			                };

			                swal({
			                    title: "Success!",
			                    text: "Voucher Verified Successfully!",
			                    confirmButtonColor: "#66BB6A",
			                    type: "success",
			                    timer: 2000,
			                    closeOnConfirm: true
			                }, function () {
			                    finishCloseHidden();
			                });

			                setTimeout(function () {
			                    finishCloseHidden();
			                }, 2000);
			            });
			
			
			
			        } else {
			            swal({
			                title: "Something went wrong!",
			                text: "Please Check Verification Code...",
			                confirmButtonColor: "#EF5350",
			                type: "error"
			            });
			        }
			    });
	       }else {
	            swal({
	                title: "Cancelled",
	                text: "Your file is safe !",
	                confirmButtonColor: "#2196F3",
	                type: "error"
	            });
	           //$('.delete_show_hide_' + cust_id).toggle();
	        }	        
	    });
  
    }
}

function check_stock(date) {
    //alert(date);
    var base_url = $('#base_url').val();
    var url = base_url + "process_waves/check_stock_validation";
    $.post(url, function (json_data) {
        var data = jQuery.parseJSON(json_data);
        //alert(data.status);
        if (data.status === 'pcs_error') {
            swal({
                title: "Error!",
                text: "Pc's Error in " + data.msg + " !",
                confirmButtonColor: "#EF5350",
                type: "error"
            });
        }
        if (data.status === 'loss_percentage_error') {
            swal({
                title: "Error!",
                text: "Loss Percentage Error in " + data.msg + " !",
                confirmButtonColor: "#EF5350",
                type: "error"
            });
        }
        if (data.status === 'true') {
            insert_check_point(date);
        }
    });
}
function insert_check_point(date){
    var base_url = $('#base_url').val();
    var url2 = base_url + "process_waves/insert_stock_check_point";
    $.post(url2, {
        end_date_time: date
    },
    function(){
        swal({
            title: "Success!",
            text: "Stock Point Created Successfully!",
            confirmButtonColor: "#66BB6A",
            type: "success",
            closeOnConfirm: true
        },function(){
            window.location.reload();
        });
    });
}

function check_stock_loop(result, result_lenght, index, date) {
    var base_url = $('#base_url').val();
    var url = base_url + "process_waves/transfer_process_waves/" + result[index]['to_id'] + '/' + result[index]['process_waves_id'] + '/true';

    var $this = $(this)
            , $remote = url
            , $modal = $('<div class="modal" id="ajaxModal"><div class="modal-body"></div></div>');
    $('.modal-body').append($modal);
    $modal.modal();
    $modal.load($remote);
    $('#ajaxModal').on('hidden.bs.modal', function () {
        $('.modal').remove();
        index++;
        if (result_lenght > (index)) {
            check_stock_loop(result, result_lenght, index);
        }
    });
}

function insert_waves() {
    $('.show_hidde').toggle();
    var base_url = $('#base_url').val();
    var url = base_url + "raw_waves/insert_in_out";
    var from_data = new FormData($("#add_in_out_form")[0]);
    var form_type = $('#form_type').val();
    var cust_name = $('#cust_id').text();
    var transactional_mode = $("input[name='transactional_mode']:checked").val();
    $.ajax({
        url: url,
        type: 'POST',
        data: from_data,
        async: false,
        success: function (json_data) {
            var data = jQuery.parseJSON(json_data);
            if (data.result === 'true' && (form_type === 'Add' || form_type === 'transfer_to_cust')) {
                swal({
                    title: "Success!",
                    text: "Raw Wave Inserted Successfully!",
                    confirmButtonColor: "#66BB6A",
                    type: "success",
                    closeOnConfirm: true
                }, function () {
                    if (transactional_mode != 'RK') {
                        if (cust_name != 'Stone') {
                            var url = base_url + "raw_waves/download_voucher/" + data.waves_id;
                            var $this = $(this)
                                    , $remote = url
                                    , $modal = $('<div class="modal" id="ajaxModal"><div class="modal-body"></div></div>');
                            $('.modal-body').append($modal);
                            $modal.modal();
                            $modal.load($remote);                            
                            $('#ajaxModal').on('hidden.bs.modal', function () {
                                if (form_type === 'transfer_to_cust') {
                                    window.location = (base_url + "process_waves/manage/87890fca66eacc10c9c7cf440a956262");                                    
                                }else{
                                    window.location.reload();
                                }
                            });                          
                        }
                    }
                    $("#cust_id").select2("val", "");
                    $("#transactional_type").select2("val", "");
                    $("#remark").select2("val", "");
                    $('#add_in_out_form')[0].reset();
                    $('.show_hidde').toggle();
                    $('#amount').attr('required', 'true');
                    $('#weight').removeAttr('required', 'true');
                    $('#purity').removeAttr('required', 'true');
                    $('#weight').val('');
                    $('#purity').val('');
                    $('.1').removeAttr('disabled', 'disabled');
                    $('.MT').hide();
                    $('.RK').show();
                    $(".control-primary").uniform({
                        radioClass: 'choice',
                        wrapperClass: 'border-primary-600 text-primary-800'
                    });
                }
                );
            }
            if (data.result === 'false' && (form_type === 'Add' || form_type === 'transfer_to_cust')) {
                swal({
                    title: "Error!",
                    text: "Wave Already Existed !",
                    confirmButtonColor: "#EF5350",
                    type: "error",
                    closeOnConfirm: true
                }, function () {
                    $("#cust_id").select2("val", "");
                    $("#transactional_type").select2("val", "");
                    $("#remark").select2("val", "");
                    $('#add_in_out_form')[0].reset();
                    $('.show_hidde').toggle();
                }
                );
            }
            if (data.result === 'true' && form_type === 'Edit') {
                swal({
                    title: "Success!",
                    text: "Updated Successfully!",
                    confirmButtonColor: "#66BB6A",
                    type: "success",
                    closeOnConfirm: true
                }, function () {
                    $('.show_hidde').toggle();
                    if (transactional_mode != 'RK') {
                        if (cust_name != 'Stone') {
                            var url = base_url + "raw_waves/download_voucher/" + data.waves_id;
                            var $this = $(this)
                                    , $remote = url
                                    , $modal = $('<div class="modal" id="ajaxModal"><div class="modal-body"></div></div>');
                            $('.modal-body').append($modal);
                            $modal.modal();
                            $modal.load($remote);
                            $('#ajaxModal').on('hidden.bs.modal', function () {
                                location.replace(base_url + "raw_waves/manage_raw_waves");
                            });
                        }
                    }
                }
                );
            }
            if (data.result === 'false' && form_type === 'Edit') {
                $('.show_hidde').toggle();
                swal({
                    title: "Error!",
                    confirmButtonColor: "#EF5350",
                    type: "error",
                    closeOnConfirm: true
                }, function () {
                    $('.show_hidde').toggle();
                });
            }
        },
        cache: false,
        contentType: false,
        processData: false
    });
}
