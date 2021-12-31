$(document).ready(function(){
  $('.bio').click(function(){
    $.ajax(
      {
        url: "./data/json/bio.json", 
        success: function(result){
          $("#bio .modal-body").html(JSON.parse(result));
        }
      });
  });
});