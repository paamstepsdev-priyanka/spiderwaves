$(function(){    
    
    var member_id = $('#chart_member_id').val();
    var base_url = $('#base_url').val();
    var measurement_height_url = base_url+"charts/measurement_height";
    $.post(measurement_height_url,{
        member_id:member_id
    },function(data){       
        var result = jQuery.parseJSON(data);
        var assessment_measurement_date = ['x',];
        if(result['assessment_date']){
            $.each(result['assessment_date'], function(index)
            {                
                assessment_measurement_date.push(result['assessment_date'][index].toString());                
            });
        }
       
       //Assessment Chart
       
       //Measurement
       
       //Measurement Height
       
        var assessment_measurement_height = ['Height (In.)',];
          if(result['measurement_height']){
            $.each(result['measurement_height'], function(index)
              {                
                  assessment_measurement_height.push(result['measurement_height'][index]);                
              });
          }
        
         
            var assessment_measurement_height_chart = c3.generate({
                bindto: '#measurement_height_chart',
                padding: {                  
                    right: 25,                  
                    left: 25,
                },
                size: { 
                   // height: 200,
                   // width: 400
                },
                data: {
                    x: 'x',            
                    columns: [
                        assessment_measurement_date,
                        assessment_measurement_height
                    ],
                    type:'area-spline'
                },       
                axis : {
                    x : {
                        label:'Date',
                        type : 'timeseries',
                        tick: {
                            format: function (x) { return x.getDate()+'/'+x.getMonth()+'/'+x.getFullYear(); }                          
                        }
                    },
                    y: {
                        label: 'Height (In.)'
                    }
                    
                }
            });
            
            //Measurement Weight
            
            var assessment_measurement_weight = ['Weight (kg.)',];
            if(result['measurement_weight']){
              $.each(result['measurement_weight'], function(index)
                {                
                    assessment_measurement_weight.push(result['measurement_weight'][index]);                
                });
            }
            
            var assessment_measurement_weight_chart = c3.generate({
                bindto: '#measurement_weight_chart',
                padding: {                  
                    right: 30,                  
                    left: 50,
                },
                size: { 
                   // height: 200,
                   // width: 400
                },
                data: {
                    x: 'x',            
                    columns: [
                        assessment_measurement_date,
                        assessment_measurement_weight
                    ],
                    type:'area-spline'
                },       
                axis : {
                    x : {
                        label:'Date',
                        type : 'timeseries',
                        tick: {
                            format: function (x) { return x.getDate()+'/'+x.getMonth()+'/'+x.getFullYear(); }                          
                        }
                    },
                    y: {
                        label: 'Weight (kg.)'
                    }
                    
                }
            });
            
            
            //Measurement Neck
            
            var assessment_measurement_neck = ['Neck (In.)',];
            if(result['measurement_neck']){
              $.each(result['measurement_neck'], function(index)
                {                
                    assessment_measurement_neck.push(result['measurement_neck'][index]);                
                });
            }
            
            var assessment_measurement_neck_chart = c3.generate({
                bindto: '#measurement_neck_chart',
                padding: {                  
                    right: 30,                  
                    left: 50,
                },
                size: { 
                   // height: 200,
                   // width: 400
                },
                data: {
                    x: 'x',            
                    columns: [
                        assessment_measurement_date,
                        assessment_measurement_neck
                    ],
                    type:'area-spline'
                },       
                axis : {
                    x : {
                        label:'Date',
                        type : 'timeseries',
                        tick: {
                            format: function (x) { return x.getDate()+'/'+x.getMonth()+'/'+x.getFullYear(); }                          
                        }
                    },
                    y: {
                        label: 'Neck (In.)'
                    }
                    
                }
            });
            
            
            //Measurement Chest
            
            var assessment_measurement_chest = ['Chest (In.)',];
            if(result['measurement_chest']){
              $.each(result['measurement_chest'], function(index)
                {                
                    assessment_measurement_chest.push(result['measurement_chest'][index]);                
                });
            }
            
            var assessment_measurement_chest_chart = c3.generate({
                bindto: '#measurement_chest_chart',
                padding: {                  
                    right: 30,                  
                    left: 50,
                },
                size: { 
                   // height: 200,
                   // width: 400
                },
                data: {
                    x: 'x',            
                    columns: [
                        assessment_measurement_date,
                        assessment_measurement_chest
                    ],
                    type:'area-spline'
                },       
                axis : {
                    x : {
                        label:'Date',
                        type : 'timeseries',
                        tick: {
                            format: function (x) { return x.getDate()+'/'+x.getMonth()+'/'+x.getFullYear(); }                          
                        }
                    },
                    y: {
                        label: 'Chest (In.)'
                    }
                    
                }
            });
            
            //Measurement Arms
            
            var assessment_measurement_arms = ['Arms (In.)',];
            if(result['measurement_arms']){
              $.each(result['measurement_arms'], function(index)
                {                
                    assessment_measurement_arms.push(result['measurement_arms'][index]);                
                });
            }
            
            var assessment_measurement_arms_chart = c3.generate({
                bindto: '#measurement_arms_chart',
                padding: {                  
                    right: 30,                  
                    left: 50,
                },
                size: { 
                   // height: 200,
                   // width: 400
                },
                data: {
                    x: 'x',            
                    columns: [
                        assessment_measurement_date,
                        assessment_measurement_arms
                    ],
                    type:'area-spline'
                },       
                axis : {
                    x : {
                        label:'Date',
                        type : 'timeseries',
                        tick: {
                            format: function (x) { return x.getDate()+'/'+x.getMonth()+'/'+x.getFullYear(); }                          
                        }
                    },
                    y: {
                        label: 'ARMS (In.)'
                    }
                    
                }
            });
            
            //Measurement Fore Arms
            
            var assessment_measurement_fore_arms = ['Fore Arms (In.)',];
            if(result['measurement_fore_arms']){
              $.each(result['measurement_fore_arms'], function(index)
                {                
                    assessment_measurement_fore_arms.push(result['measurement_fore_arms'][index]);                
                });
            }
            
            var assessment_measurement_fore_arms_chart = c3.generate({
                bindto: '#measurement_fore_arms_chart',
                padding: {                  
                    right: 30,                  
                    left: 50,
                },
                size: { 
                   // height: 200,
                   // width: 400
                },
                data: {
                    x: 'x',            
                    columns: [
                        assessment_measurement_date,
                        assessment_measurement_fore_arms
                    ],
                    type:'area-spline'
                },       
                axis : {
                    x : {
                        label:'Date',
                        type : 'timeseries',
                        tick: {
                            format: function (x) { return x.getDate()+'/'+x.getMonth()+'/'+x.getFullYear(); }                          
                        }
                    },
                    y: {
                        label: 'Fore ARMS (In.)'
                    }
                    
                }
            });
            
            //Measurement Upper Abs
            
            var assessment_measurement_upper_abs = ['Upper Abs (In.)',];
            if(result['measurement_upper_abs']){
              $.each(result['measurement_upper_abs'], function(index)
                {                
                    assessment_measurement_upper_abs.push(result['measurement_upper_abs'][index]);                
                });
            }
            
            var assessment_measurement_upper_abs_chart = c3.generate({
                bindto: '#measurement_upper_abs_chart',
                padding: {                  
                    right: 30,                  
                    left: 50,
                },
                size: { 
                   // height: 200,
                   // width: 400
                },
                data: {
                    x: 'x',            
                    columns: [
                        assessment_measurement_date,
                        assessment_measurement_upper_abs
                    ],
                    type:'area-spline'
                },       
                axis : {
                    x : {
                        label:'Date',
                        type : 'timeseries',
                        tick: {
                            format: function (x) { return x.getDate()+'/'+x.getMonth()+'/'+x.getFullYear(); }                          
                        }
                    },
                    y: {
                        label: 'Upper Abs (In.)'
                    }
                    
                }
            });
            
            //Measurement Hips
            
            var assessment_measurement_hips = ['Hips (In.)',];
            if(result['measurement_hips']){
              $.each(result['measurement_hips'], function(index)
                {                
                    assessment_measurement_hips.push(result['measurement_hips'][index]);                
                });
            }
            
            var assessment_measurement_hips_chart = c3.generate({
                bindto: '#measurement_hips_chart',
                padding: {                  
                    right: 30,                  
                    left: 50,
                },
                size: { 
                   // height: 200,
                   // width: 400
                },
                data: {
                    x: 'x',            
                    columns: [
                        assessment_measurement_date,
                        assessment_measurement_hips
                    ],
                    type:'area-spline'
                },       
                axis : {
                    x : {
                        label:'Date',
                        type : 'timeseries',
                        tick: {
                            format: function (x) { return x.getDate()+'/'+x.getMonth()+'/'+x.getFullYear(); }                          
                        }
                    },
                    y: {
                        label: 'Hips (In.)'
                    }
                    
                }
            });
            
            //Measurement Thighs Chart
            
            var assessment_measurement_thighs = ['Thighs (In.)',];
            if(result['measurement_thighs']){
              $.each(result['measurement_thighs'], function(index)
                {                
                    assessment_measurement_thighs.push(result['measurement_thighs'][index]);                
                });
            }
            
            var assessment_measurement_thighs_chart = c3.generate({
                bindto: '#measurement_thighs_chart',
                padding: {                  
                    right: 30,                  
                    left: 50,
                },
                size: { 
                   // height: 200,
                   // width: 400
                },
                data: {
                    x: 'x',            
                    columns: [
                        assessment_measurement_date,
                        assessment_measurement_thighs
                    ],
                    type:'area-spline'
                },       
                axis : {
                    x : {
                        label:'Date',
                        type : 'timeseries',
                        tick: {
                            format: function (x) { return x.getDate()+'/'+x.getMonth()+'/'+x.getFullYear(); }                          
                        }
                    },
                    y: {
                        label: 'Thighs (In.)'
                    }
                    
                }
            });
    });
});





