jQuery(document).ready(function($) {
    //console.log();
    $('#calendar').fullCalendar({
      themeSystem: 'bootstrap4',
      header: {
        left: 'prev,next today',
        center: 'title',
        right: 'month,agendaDay,listMonth'
      },
      weekNumbers: true,
      eventLimit: true, 
      eventSources: [{
        url : '/wp-content/plugins/rally-rental/entries/processing-entries.json',
        color: 'green',
        textColor: 'white'
      },
      {
        url : '/wp-content/plugins/rally-rental/entries/approved-entries.json',
        color: 'red',
        textColor: 'white'
      }]
    });
    $('input:radio[name="trip_type"]').change(function(){
      if($(this).val() == 'roundtrip'){
        $('.end_date').show();
      } else {
        $('.end_date').hide();
      }
    });
    $("select").on('focus', function () {
        $("select").find("option[value='"+ $(this).val() + "']").attr('disabled', false);
    }).change(function() {
        $("select").not(this).find("option[value='"+ $(this).val() + "']").attr('disabled', true);
    });
    $(".edit-button").on("click", function(e){
      e.preventDefault();  
      var rental_id = ($(this).attr("id"));
       $.ajax({
          url : rallyrental_ajax.ajax_url,
          type : 'post',
          data : {
              action : 'rallyrental_form',
              rental_id : rental_id
          },
          dataType: 'json',
          success : function(data) {
              //console.log(data);
              if((data[0].trip_type) == 'roundtrip') { $('.end_date').show(); }
              $('#data_Modal input[name=rental_id]').val(data[0].id);  
              $('#data_Modal input[name=name]').val(data[0].name);  
              $('#data_Modal input[name=email]').val(data[0].email);  
              $('#data_Modal input[name=phone]').val(data[0].phone);  
              $('#data_Modal select[name=pick_up]').val(data[0].pick_up);  
              $('#data_Modal select[name=drop_off]').val(data[0].drop_off);  
              $('#data_Modal input[name=trip_type]').val([data[0].trip_type]);
              $('#data_Modal input[name=date]').val(data[0].start_date);   
              $('#data_Modal input[name=end_date]').val(data[0].end_date);   
              $('#data_Modal input[name=time]').val(data[0].time);   
              $('#data_Modal input[name=payment]').val([data[0].payment]); 
              $('#data_Modal select[name=status]').val(data[0].status);  
              $('#data_Modal select[name=car]').val(data[0].car);  
              $('#data_Modal textarea[name=invoice]').val(data[0].invoice);  
              $('#data_Modal').modal('show');  
          }
      });
    });
    // reset form inside modal on close
    $('#data_Modal').on('hidden.bs.modal', function(){
        $(this).find('form')[0].reset();
        $('label.error').hide();
    });
    $(".add-new").on("click", function(e){
        $('#data_Modal').modal('show');
    });
});