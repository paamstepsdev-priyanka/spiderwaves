$(function() {
//    $('.multiselect-max-height').multiselect({
//        maxHeight: 200
//    });
//    $('.multiselect-full').multiselect({
//        buttonWidth: '100%'
//    });
 //   $('.pickatime').pickatime();
    
//    $('.multiselect-full-featured').multiselect({
//        includeSelectAllOption: true,
//        enableFiltering: true,
//        enableCaseInsensitiveFiltering: false,
//        templates: {
//            filter: '<li class="multiselect-item multiselect-filter"><i class="icon-search4"></i> <input class="form-control" type="text"></li>'
//        },
//        onChange: function() {
//            $.uniform.update();
//        }
//    });
   
    $(".styled, .multiselect-container input").uniform({ radioClass: 'choice'});
    $(".control-primary").uniform({
        radioClass: 'choice',
        wrapperClass: 'border-primary-600 text-primary-800'
    });
   // var primary = document.querySelector('.switchery-primary');
   // var switchery = new Switchery(primary, { color: '#2196F3' });
  //Select2
    $('.select2').select2();
    
    $(document).on('click', '[data-toggle="ajaxModal"]',
        function(e) {
          $('#ajaxModal').remove();
          e.preventDefault();
          var $this = $(this)
            , $remote = $this.data('remote') || $this.attr('href')
            , $modal = $('<div class="modal" id="ajaxModal"><div class="modal-body"></div></div>');
          $('body').append($modal);
          $modal.modal();
          $modal.load($remote);       
        }
    );
      
      
$('.sweet_success').on('click', function() {
    swal({
        title: "Success!",
        //text: "You clicked the button!",
        confirmButtonColor: "#66BB6A",
        type: "success"
    });
});


    //
    // Validation
    //


    // Initialize validation
    var validate = {
        ignore: 'input[type=hidden], .select2-input',
        errorClass: 'validation-error-label',
        successClass: 'validation-valid-label',
        highlight: function(element, errorClass) {
            $(element).removeClass(errorClass);
        },
        unhighlight: function(element, errorClass) {
            $(element).removeClass(errorClass);
        },
        errorPlacement: function(error, element) {
            if (element.parents('div').hasClass("checker") || element.parents('div').hasClass("choice") || element.parent().hasClass('bootstrap-switch-container') ) {
                if(element.parents('label').hasClass('checkbox-inline') || element.parents('label').hasClass('radio-inline')) {
                    error.appendTo( element.parent().parent().parent().parent() );
                }
                 else {
                    error.appendTo( element.parent().parent().parent().parent().parent() );
                }
            }
            else if (element.parents('div').hasClass('checkbox') || element.parents('div').hasClass('radio')) {
                error.appendTo( element.parent().parent().parent() );
            }
            else if (element.parents('label').hasClass('checkbox-inline') || element.parents('label').hasClass('radio-inline')) {
                error.appendTo( element.parent().parent() );
            }
            else if (element.parent().hasClass('uploader') || element.parents().hasClass('input-group')) {
                error.appendTo( element.parent().parent() );
            }
            else {
                error.insertAfter(element);
            }
        },
        rules: {
            email: {
                email: true
            },
            number: {
                digits:true
            }
        }
    }

});
