$body = $("body");

$(document).on({
  ajaxStart: function() { 
    $body.addClass("loading");    
  },
  ajaxStop: function() { 
    $body.removeClass("loading"); 
  }    
});

var d = new Date();
$("#year").html(d.getFullYear());

$(document).ready(function(){
  $('.bio').click(function(){
    $.ajax({
      url: "./data/json/kontak.json",
      success: function(result) {
        var data  = result;
        var img   = '<img src="'+data['image']+'" width="100" height="100" class="rounded-circle" id="profil-img">';
        var email = '<span class="fa fa-envelope"></span> Email : <a href="mailto:' + data['email'] + '">' + data['email'] + '</a>';
        var ig    = '<span class="fa fa-instagram"></span> Instagram : <a href="//instagram.com/' + data['instagram'] + '" target="_blank">@' + data['instagram'] + '</a>';

        // merge
        var kontak = "<center>" + img + "<br/>" + email + "<br/>" + ig + "</center><hr/>";

        $("#bio .modal-body div").html(kontak);
      },
      beforeSend: function() {
        $body.addClass("loading");
      },
      complete: function(){
        $body.removeClass("loading");
      }
    });

    $.ajax({
      url: "./data/json/info.json", 
      success: function(result){
        $("#bio .modal-body p").html(JSON.parse(result));
      },
      beforeSend: function() {
        $body.addClass("loading");
      },
      complete: function(){
        $body.removeClass("loading");
      }
    });
  });
});